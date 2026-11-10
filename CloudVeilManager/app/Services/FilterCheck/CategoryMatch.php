<?php

namespace App\Services\FilterCheck;

/**
 * A list that matched a URL, and the rule that made it match.
 */
final class CategoryMatch
{
    public function __construct(
        public readonly FilterCategory $category,
        public readonly AbpRule $rule,
    ) {}

    /**
     * Whether this match allows the URL: an allow list, or an `@@` rule anywhere.
     */
    public function allows(): bool
    {
        return $this->category->isWhitelist() || $this->rule->exception;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'list' => $this->category->list,
            'category' => $this->category->category,
            'list_type' => $this->category->listType,
            'source' => $this->category->source,
            'effect' => $this->allows() ? 'allow' : 'block',
            'rule' => $this->rule->raw,
        ];
    }
}
