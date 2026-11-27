<?php

namespace App\Services\FilterCheck;

use RuntimeException;

/**
 * A rule needed too much work to match against a URL (usually many wildcards),
 * so the check stopped instead of tying up the server.
 */
class RuleTooComplexException extends RuntimeException
{
    public function __construct(public readonly AbpRule $rule, public readonly ?FilterCategory $category = null)
    {
        parent::__construct('A rule is too complex to check: '.$rule->raw.($category ? " ({$category->category} list)" : '').'.');
    }

    public function inCategory(FilterCategory $category): self
    {
        return new self($this->rule, $category);
    }
}
