<?php

namespace App\Services\FilterCheck;

use App\Client\PlainTextFilteringListType;

/**
 * One list as the client loads it: its type, and the rules from it that could
 * apply to the URLs being checked.
 */
class FilterCategory
{
    public const SOURCE_GROUP = 'group';

    public const SOURCE_CUSTOM = 'custom';

    /** @var array<int, AbpRule> */
    private array $excludes = [];

    /** @var array<int, AbpRule> */
    private array $includes = [];

    /** @var array<string, AbpRule> */
    private array $domains = [];

    public function __construct(
        public readonly string $list,
        public readonly string $category,
        public readonly string $listType,
        public readonly string $source,
    ) {}

    public function add(AbpRule $rule): void
    {
        if ($rule->isDomainRule()) {
            // Later lines replace earlier ones, as in the client's map.
            $this->domains[$rule->domainKey] = $rule;
        } elseif ($rule->exception) {
            $this->excludes[] = $rule;
        } else {
            $this->includes[] = $rule;
        }
    }

    public function isBypass(): bool
    {
        return $this->listType === PlainTextFilteringListType::BypassList;
    }

    public function isWhitelist(): bool
    {
        return $this->listType === PlainTextFilteringListType::Whitelist;
    }

    /**
     * Test one URL: `@@` rules, then other rules, then the domain map from the
     * shortest domain up. The first hit decides this list's answer.
     */
    public function match(AbpRuleMatcher $matcher, string $url, string $host, string $referer = ''): ?CategoryMatch
    {
        try {
            return $this->firstMatch($matcher, $url, $host, $referer);
        } catch (RuleTooComplexException $e) {
            throw $e->inCategory($this);
        }
    }

    private function firstMatch(AbpRuleMatcher $matcher, string $url, string $host, string $referer): ?CategoryMatch
    {
        foreach ($this->excludes as $rule) {
            if ($matcher->matches($rule, $url, $referer)) {
                return new CategoryMatch($this, $rule);
            }
        }

        foreach ($this->includes as $rule) {
            if ($matcher->matches($rule, $url, $referer)) {
                return new CategoryMatch($this, $rule);
            }
        }

        $labels = explode('.', $host);
        if (count($labels) < 2 || $this->domains === []) {
            return null;
        }

        $domain = $labels[count($labels) - 1];
        for ($i = count($labels) - 2; $i >= 0; $i--) {
            $domain = $labels[$i].'.'.$domain;
            if (isset($this->domains[$domain])) {
                return new CategoryMatch($this, $this->domains[$domain]);
            }
        }

        return null;
    }
}
