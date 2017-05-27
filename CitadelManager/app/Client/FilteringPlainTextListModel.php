<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Client;

class PlainTextFilteringListType {

    /**
     * A plain text file where each line contains a domain or URL that should be blacklisted.
     */
    const Blacklist = 'Blacklist';

    /**
     * A plain text file where each line contains a domain or URL that should be whitelisted.
     */
    const Whitelist = 'Whitelist';

    /**
     * A plain text file where each line contains a domain or URL that should be blacklisted,
     * but also should also be capable of being transformed on-demand into a whitelist.
     */
    const BypassList = 'BypassList';

    /**
     * A plain text file where each line contains arbitrary text that, if detected within a HTML
     * text payload, should trigger a block action.
     */
    const TextTrigger = 'TextTrigger';

}

/**
 * The FilteringPlainTextListModel represents, as generically as possible, a plain text data
 * file that is intended to be used for content filtering. This plain text file may be a list of
 * domains, urls, text triggers, or something else.
 *
 * This model contains a relative path to the plain text file within a parent zip container. It
 * also gives an enumeration indicating the type or intent of the text data within the file.
 */
class FilteringPlainTextListModel {

    /**
     * The type of plain text list that this is.
     */
    public $ListType;

    /**
     * The relative path to the list file inside the parent zip container.
     */
    public $RelativeListPath;

    public function __construct(string $listType, string $relativeListPath) {
        if (!isset($listType)) {
            error_log('List type is not defined.');
            throw new \Exception('List type is not defined.');
        }

        if (!isset($relativeListPath)) {
            error_log('Relative path is not defined.');
            throw new \Exception('Relative path is not defined.');
        }

        if (strlen($listType) <= 0) {
            error_log('List type is an empty string.');
            throw new \Exception('List type is an empty string.');
        }

        if (strlen($relativeListPath) <= 0) {
            error_log('Relative path is an empty string.');
            throw new \Exception('Relative path is an empty string.');
        }

        // Should ensure that the list type is defined in
        // our pretend enum class PlainTextFilteringListType.
        if (!defined(__NAMESPACE__ . '\PlainTextFilteringListType::' . $listType)) {
            error_log('List type string value is not a known, valid list type enumeration.');
            throw new \Exception('List type string value is not a known, valid list type enumeration.');
        }

        $this->ListType = iconv(mb_detect_encoding($listType, mb_detect_order(), true), "UTF-8", $listType);
        $this->RelativeListPath = iconv(mb_detect_encoding($relativeListPath, mb_detect_order(), true), "UTF-8", $relativeListPath);
    }

}
