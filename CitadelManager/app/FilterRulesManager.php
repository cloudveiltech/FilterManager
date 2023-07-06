<?php

/*
 * Copyright © 2018 CloudVeil Technology, Inc.
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App;

use Doctrine\Common\Annotations\IndexedReader;
use Illuminate\Support\Facades\DB;
use Log;
use SplFileObject;

class FilterRulesManager
{
    const TYPES = [
        'Filters' => 'rules',
        'Triggers' => 'triggers'
    ];


    public function getRuleDataPath(): string
    {
        $storageDir = resource_path();
        $rulesZipPath = $storageDir . DIRECTORY_SEPARATOR . 'global-rules.zip';
        return $rulesZipPath;
    }

    public function getFilename($listNamespace, $listCategory, $filename, $separatorChar = '.')
    {
        return $separatorChar . $listNamespace .
            $separatorChar . $listCategory .
            $separatorChar . $filename;
    }

    public function getEtag($path)
    {
        return hash_file('sha1', $path);
    }

    public function getRulesetPath($namespace, $category, $type)
    {
        $filename = $this->getFilename($namespace, $category, "$type.txt");

        $storageDir = resource_path() . DIRECTORY_SEPARATOR . "rules" . DIRECTORY_SEPARATOR . $this->getDirForRuleset($category);
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        return $storageDir . $filename;
    }

    private function getDirForRuleset(string $category): string
    {
        return $category[0] . DIRECTORY_SEPARATOR . $category[1] . DIRECTORY_SEPARATOR;
    }

    public function buildRuleData()
    {
        Log::debug('Building Rule Data');

        foreach (FilterList::cursor() as $list) {
            if (!is_null($list)) {
                Log::debug('List: ' . $list->namespace . ' Category: ' . $list->category . ' Type: ' . $list->type . ' ID: ' . $list->id);

                $listNamespace = $list->namespace;
                $listCategory = $list->category;
                $listType = $list->type;

                $mappedFileType = self::TYPES[$listType];
                $this->buildRuleset($listNamespace, $listCategory, $mappedFileType, $list);
            }
        }
    }

    public function buildRuleset($namespace, $category, $type, $filterList)
    {
        $filename = $this->getFilename($namespace, $category, "$type.txt");

        $filePath = $this->buildFilePath($filename, $category);

        $filterList->file_sha1 = $this->getEtag($filePath);
        $filterList->entries_count = $this->getLines($filePath);
        $filterList->save();

        return $filePath;
    }

    // Helps reduce memory usage for rule file building.
    public function buildFilePath($filename, $category)
    {
        $storageDir = resource_path() . DIRECTORY_SEPARATOR . "rules" . DIRECTORY_SEPARATOR . $this->getDirForRuleset($category);
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $filePath = $storageDir . DIRECTORY_SEPARATOR . $filename;
        return $filePath;
    }

    // Helps reduce memory usage for rule file building.
    public function buildFileFromSpl(SplFileObject $inputFile, FilterList $list, $convertToAbp, $appendToEndOfFile = false)
    {
        if ($convertToAbp) {
            $lineFeedFunc = function (string $in): string {
                return $this->formatStringAsAbpFilter(trim($in));
            };
        } else {
            $lineFeedFunc = function (string $in): string {
                return trim($in);
            };
        }

        $listNamespace = $list->namespace;
        $listCategory = $list->category;
        $listType = $list->type;

        $storageDir = resource_path() . DIRECTORY_SEPARATOR . "rules" . DIRECTORY_SEPARATOR . $this->getDirForRuleset($listCategory);
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $mappedFileType = self::TYPES[$listType];
        $filename = $this->getFilename($listNamespace, $listCategory, "$mappedFileType.txt");

        $filePath = $storageDir . $filename;
        Log::debug('Opening File to write filters to it: ' . $filePath);
        $file = fopen($filePath, $appendToEndOfFile ? 'a' : 'w');

        foreach ($inputFile as $lineNumber => $rule) {
            $rule = $lineFeedFunc($rule);
            fprintf($file, "%s\n", $rule);
        }

        fclose($file);
        Log::debug('Closing File to write filters to it: ' . $filePath);

        $list->file_sha1 = $this->getEtag($filePath);
        $list->entries_count = $this->getLines($filePath);

        return $filePath;
    }

    private function formatStringAsAbpFilter(string $input): string
    {
        if (strlen($input) <= 0) {
            return $input;
        }

        return '||' . str_replace('/', '^', $input);
    }

    function getLines($file)
    {
        $file = new \SplFileObject($file, 'r');
        $file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY |
            SplFileObject::DROP_NEW_LINE);
        $file->seek(PHP_INT_MAX);

        return $file->key();
    }

    public function deleteFiles(FilterList $list)
    {
        $listNamespace = $list->namespace;
        $listCategory = $list->category;
        $listType = $list->type;

        $mappedFileType = self::TYPES[$listType];

        $storageDir = resource_path() . DIRECTORY_SEPARATOR . "rules" . DIRECTORY_SEPARATOR . $this->getDirForRuleset($listCategory);

        $filename = $storageDir . $this->getFilename($listNamespace, $listCategory, "$mappedFileType.txt");

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}

