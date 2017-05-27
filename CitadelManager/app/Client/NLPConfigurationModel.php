<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Client;

/**
 * The NLPConfigurationModel class represents an Apache OpenNLP model file along with a list of
 * all categories within the listed NLP model file that are to be used for content
 * classification.
 *
 * That is to say that this model class gives a relative path an Apache OpenNLP model file
 * within a zip container, and then also lists categories within the model that were selected
 * for use. All other categories inside the model file are ignored.
 *
 * This configuration is set externally here on the server side, and is meant to
 * be consumed in a read-only fashion by the client.
 */
class NLPConfigurationModel {

    /**
     * The relative path to the Apache OpenNLP model file inside the parent zip container.
     */
    public $RelativeModelPath;

    /**
     * A list of categories selected from within the Apache OpenNLP model that, should they be
     * returned from a text classification result, be considered a positive match and trigger a
     * block action.
     */
    public $SelectedCategoryNames;

    public function __construct(string $relativePath, array $selectedCategories) {
        if (!isset($relativePath)) {
            throw new \Exception('Relative path is not defined.');
        }

        if (!isset($selectedCategories)) {
            throw new \Exception('Selected categories array is not defined.');
        }

        if (strlen($relativePath) <= 0) {
            throw new \Exception('Relative path is an empty string.');
        }

        if (count($selectedCategories) <= 0) {
            throw new \Exception('Selected categories is an empty array.');
        }

        $this->RelativeModelPath = iconv(mb_detect_encoding($relativePath, mb_detect_order(), true), "UTF-8", $relativePath);

        $this->SelectedCategoryNames = array();

        foreach ($selectedCategories as $selectedCat) {
            $convertedCatName = iconv(mb_detect_encoding($selectedCat, mb_detect_order(), true), "UTF-8", $selectedCat);
            array_push($this->SelectedCategoryNames, $convertedCatName);
        }
    }

}
