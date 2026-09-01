<?php

namespace App\Services;

/**
 * The possible results of the filter import gate.
 *
 * PHP 7.0 has no backed enums, so these constants are the stable public
 * outcome values shared with CloudVeilManager.
 */
final class FilterImportOutcome
{
    /** The gate permits the caller to import the category. */
    const IMPORTED = 'imported';

    const SKIPPED_ALREADY_CURRENT = 'skipped-already-current';

    const NOT_IN_ALLOWLIST = 'not-in-allowlist';

    const DENIED = 'denied';

    const OBJECT_MISSING = 'object-missing';

    const DISK_ERROR = 'disk-error';

    private function __construct()
    {
    }
}
