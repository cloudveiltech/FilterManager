<?php

namespace App\Services;

enum FilterImportOutcome: string
{
    /** The gate permits the caller to import the category. */
    case IMPORTED = 'imported';

    case SKIPPED_ALREADY_CURRENT = 'skipped-already-current';

    case NOT_IN_ALLOWLIST = 'not-in-allowlist';

    case DENIED = 'denied';

    case OBJECT_MISSING = 'object-missing';

    case DISK_ERROR = 'disk-error';
}
