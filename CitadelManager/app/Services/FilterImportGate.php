<?php

namespace App\Services;

use App\FilterList;
use App\FilterRulesManager;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Decides whether an export object may be handed to the existing importer.
 *
 * Eligibility comes from the shared filter_lists allowlist. Import state comes
 * only from this application's local rule files, whose paths are resolved by
 * FilterRulesManager. The gate never creates filter_lists rows and never
 * downloads or imports an archive itself.
 */
final class FilterImportGate
{
    const DEFAULT_NAMESPACE = 'default';

    const EXPORT_DISK = 'export';

    const DEFAULT_CLOCK_TOLERANCE_SECONDS = 5;

    /** @var Filesystem */
    private $exportDisk;

    /** @var FilterRulesManager */
    private $rulesManager;

    /** @var array */
    private $deniedCategories;

    /** @var int */
    private $clockToleranceSeconds;

    /** @var callable */
    private $hasFilterLists;

    /**
     * @param Filesystem|null $exportDisk
     * @param FilterRulesManager|null $rulesManager
     * @param array|null $deniedCategories
     * @param int|null $clockToleranceSeconds
     * @param callable|null $hasFilterLists
     */
    public function __construct(
        $exportDisk = null,
        $rulesManager = null,
        $deniedCategories = null,
        $clockToleranceSeconds = null,
        $hasFilterLists = null
    ) {
        $this->exportDisk = $exportDisk ?: Storage::disk(self::EXPORT_DISK);
        $this->rulesManager = $rulesManager ?: new FilterRulesManager;

        $configuredDeniedCategories = $deniedCategories !== null
            ? $deniedCategories
            : config('filter_imports.deny', []);
        $this->deniedCategories = array_values($configuredDeniedCategories);

        $configuredClockTolerance = $clockToleranceSeconds !== null
            ? $clockToleranceSeconds
            : (int) config(
                'filter_imports.clock_tolerance_seconds',
                self::DEFAULT_CLOCK_TOLERANCE_SECONDS
            );
        $this->clockToleranceSeconds = max(0, (int) $configuredClockTolerance);

        $this->hasFilterLists = $hasFilterLists ?: function ($namespace, $category) {
            return FilterList::query()
                ->where('namespace', $namespace)
                ->where('category', $category)
                ->exists();
        };
    }

    /**
     * Extract a category only from an export_<category>.zip object name.
     *
     * A null return is intentional: names such as export.zip are not
     * categories and must never become candidates for the import path.
     *
     * @param string $objectKey
     * @return string|null
     */
    public function categoryFromObjectKey($objectKey)
    {
        $filename = basename($objectKey);

        if (preg_match('/^export_(?<category>[^\/]+)\.zip$/D', $filename, $matches) !== 1) {
            return null;
        }

        return $matches['category'] !== '' ? $matches['category'] : null;
    }

    /**
     * Decide whether a bucket object should be handed to the importer.
     *
     * Pass the LastModified value from a bucket listing when it is already
     * available. If it is omitted, this method verifies the object exists and
     * reads its LastModified from the export disk. The null return is only for
     * structurally invalid/non-category object names.
     *
     * @param string $objectKey
     * @param int|DateTimeInterface|null $objectLastModified
     * @return FilterImportDecision|null
     */
    public function decide($objectKey, $objectLastModified = null)
    {
        $category = $this->categoryFromObjectKey($objectKey);

        if ($category === null) {
            return null;
        }

        $resolvedLastModified = $this->resolveObjectLastModified(
            $objectKey,
            $objectLastModified,
            $category
        );

        if ($resolvedLastModified instanceof FilterImportDecision) {
            return $resolvedLastModified;
        }

        $lowercaseDeniedCategories = array_map('strtolower', $this->deniedCategories);
        if (in_array(strtolower($category), $lowercaseDeniedCategories, true)) {
            return new FilterImportDecision(
                FilterImportOutcome::DENIED,
                $objectKey,
                $category,
                $resolvedLastModified,
                null,
                'The category is present in filter_imports.deny.'
            );
        }

        try {
            $isAllowlisted = call_user_func(
                $this->hasFilterLists,
                self::DEFAULT_NAMESPACE,
                $category
            );
        } catch (Throwable $exception) {
            return $this->diskError(
                $objectKey,
                $category,
                $resolvedLastModified,
                'Unable to inspect the filter_lists allowlist: '.$exception->getMessage()
            );
        }

        if (!$isAllowlisted) {
            return new FilterImportDecision(
                FilterImportOutcome::NOT_IN_ALLOWLIST,
                $objectKey,
                $category,
                $resolvedLastModified,
                null,
                'No default filter_lists row exists for the category.'
            );
        }

        $localRules = $this->inspectLocalRules($category);

        if ($localRules['error'] !== null) {
            return $this->diskError(
                $objectKey,
                $category,
                $resolvedLastModified,
                $localRules['error']
            );
        }

        if ($localRules['missing']) {
            return new FilterImportDecision(
                FilterImportOutcome::IMPORTED,
                $objectKey,
                $category,
                $resolvedLastModified,
                null,
                'A local rule file is missing: '.$localRules['missingFilename'].'.'
            );
        }

        $newestLocalMtime = $localRules['newest'];

        if ($newestLocalMtime === null) {
            return $this->diskError(
                $objectKey,
                $category,
                $resolvedLastModified,
                'No rule file types are configured for the category.'
            );
        }

        if ($newestLocalMtime + $this->clockToleranceSeconds >= $resolvedLastModified) {
            return new FilterImportDecision(
                FilterImportOutcome::SKIPPED_ALREADY_CURRENT,
                $objectKey,
                $category,
                $resolvedLastModified,
                $newestLocalMtime,
                'The newest local rule file is current within the clock tolerance.'
            );
        }

        return new FilterImportDecision(
            FilterImportOutcome::IMPORTED,
            $objectKey,
            $category,
            $resolvedLastModified,
            $newestLocalMtime,
            'The bucket object is newer than the newest local rule file.'
        );
    }

    /**
     * @param string $objectKey
     * @param int|DateTimeInterface|null $objectLastModified
     * @param string $category
     * @return int|FilterImportDecision
     */
    private function resolveObjectLastModified($objectKey, $objectLastModified, $category)
    {
        if ($objectLastModified instanceof DateTimeInterface) {
            return $objectLastModified->getTimestamp();
        }

        if ($objectLastModified !== null) {
            return $objectLastModified;
        }

        try {
            if (!$this->exportDisk->exists($objectKey)) {
                return new FilterImportDecision(
                    FilterImportOutcome::OBJECT_MISSING,
                    $objectKey,
                    $category,
                    null,
                    null,
                    'The bucket object does not exist on the export disk.'
                );
            }

            return $this->exportDisk->lastModified($objectKey);
        } catch (Throwable $exception) {
            return $this->diskError(
                $objectKey,
                $category,
                null,
                'Unable to read the bucket object metadata: '.$exception->getMessage()
            );
        }
    }

    /**
     * @param string $category
     * @return array
     */
    private function inspectLocalRules($category)
    {
        $newest = null;
        $missingFilename = null;

        foreach (FilterRulesManager::TYPES as $mappedType) {
            try {
                set_error_handler(function ($severity, $message, $file, $line) {
                    throw new \ErrorException($message, 0, $severity, $file, $line);
                });

                try {
                    $filename = $this->rulesManager->getFilename(
                        self::DEFAULT_NAMESPACE,
                        $category,
                        $mappedType.'.txt'
                    );
                    $path = $this->rulesManager->getRulesetPath(
                        self::DEFAULT_NAMESPACE,
                        $category,
                        $mappedType
                    );
                } finally {
                    restore_error_handler();
                }
            } catch (Throwable $exception) {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'Unable to resolve the local rule file path: '.$exception->getMessage(),
                ];
            }

            if (!is_string($filename) || !is_string($path) || $path === '') {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'The local rule path resolver returned an invalid path.',
                ];
            }

            clearstatcache(true, $path);

            if (!@file_exists($path)) {
                $directory = dirname($path);

                if (@file_exists($directory) && !@is_readable($directory)) {
                    return [
                        'missing' => false,
                        'missingFilename' => null,
                        'newest' => $newest,
                        'error' => 'The local rule directory is not readable: '.$directory,
                    ];
                }

                if ($missingFilename === null) {
                    $missingFilename = $filename;
                }

                continue;
            }

            if (!@is_file($path)) {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'The local rule path is not a regular file: '.$path,
                ];
            }

            $mtime = @filemtime($path);

            if ($mtime === false) {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'Unable to read the local rule file mtime: '.$path,
                ];
            }

            $newest = $newest === null ? $mtime : max($newest, $mtime);
        }

        return [
            'missing' => count(FilterRulesManager::TYPES) > 0 && $newest === null,
            'missingFilename' => $missingFilename,
            'newest' => $newest,
            'error' => null,
        ];
    }

    /**
     * @param string $objectKey
     * @param string $category
     * @param int|null $objectLastModified
     * @param string $reason
     * @return FilterImportDecision
     */
    private function diskError($objectKey, $category, $objectLastModified, $reason)
    {
        return new FilterImportDecision(
            FilterImportOutcome::DISK_ERROR,
            $objectKey,
            $category,
            $objectLastModified,
            null,
            $reason
        );
    }
}
