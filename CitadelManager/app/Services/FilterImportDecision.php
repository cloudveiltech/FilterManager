<?php

namespace App\Services;

/**
 * Value object returned by FilterImportGate::decide().
 *
 * The properties are intentionally public to preserve the reference
 * implementation's small, inspection-friendly result API. PHP 7.0 has no
 * readonly classes or promoted constructor properties, so assignment is
 * performed by a normal constructor.
 */
final class FilterImportDecision
{
    /** @var string */
    public $outcome;

    /** @var string|null */
    public $objectKey;

    /** @var string|null */
    public $category;

    /** @var int|null */
    public $objectLastModified;

    /** @var int|null */
    public $newestLocalMtime;

    /** @var string|null */
    public $reason;

    /**
     * @param string $outcome
     * @param string|null $objectKey
     * @param string|null $category
     * @param int|null $objectLastModified
     * @param int|null $newestLocalMtime
     * @param string|null $reason
     */
    public function __construct(
        $outcome,
        $objectKey = null,
        $category = null,
        $objectLastModified = null,
        $newestLocalMtime = null,
        $reason = null
    ) {
        $this->outcome = $outcome;
        $this->objectKey = $objectKey;
        $this->category = $category;
        $this->objectLastModified = $objectLastModified;
        $this->newestLocalMtime = $newestLocalMtime;
        $this->reason = $reason;
    }

    /**
     * Whether the caller should hand this object to the existing importer.
     *
     * The outcome is named "imported" to match the operator-facing result
     * vocabulary. The gate itself does not import anything; it authorizes the
     * caller to do so.
     *
     * @return bool
     */
    public function shouldImport()
    {
        return $this->outcome === FilterImportOutcome::IMPORTED;
    }
}
