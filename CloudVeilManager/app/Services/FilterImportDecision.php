<?php

namespace App\Services;

final readonly class FilterImportDecision
{
    public function __construct(
        public FilterImportOutcome $outcome,
        public ?string $objectKey = null,
        public ?string $category = null,
        public ?string $reason = null,
    ) {}

    /**
     * Whether the caller should hand this object to the existing importer.
     *
     * The outcome is named "imported" to match the operator-facing result
     * vocabulary. The gate itself does not import anything; it authorizes the
     * caller to do so.
     */
    public function shouldImport(): bool
    {
        return $this->outcome === FilterImportOutcome::IMPORTED;
    }
}
