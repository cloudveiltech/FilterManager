<?php

namespace App\Services\FilterCheck;

/**
 * One parsed Adblock Plus rule, as the client filter (patriciy/adblock) sees it.
 *
 * A rule is either a plain domain rule (`||example.com`), which the clients keep
 * in a per-list domain map, or a chain of parts matched against the full URL.
 */
final class AbpRule
{
    public const EXACT = 'exact';

    public const WILDCARD = 'wildcard';

    public const SEPARATOR = 'separator';

    public const START_ANCHOR = 'start_anchor';

    public const DOMAIN_ANCHOR = 'domain_anchor';

    public const SUBSTRING = 'substring';

    public const REFERER = 'referer';

    /**
     * @param  string  $raw  The rule line as written in the list (trimmed).
     * @param  bool  $exception  True for `@@` rules, which allow instead of block.
     * @param  string|null  $domainKey  Set for plain domain rules: the domain-map key.
     * @param  array<int, array{0: string, 1: string}>  $parts  The rewritten match chain for other rules.
     */
    public function __construct(
        public readonly string $raw,
        public readonly bool $exception,
        public readonly ?string $domainKey,
        public readonly array $parts = [],
    ) {}

    public function isDomainRule(): bool
    {
        return $this->domainKey !== null;
    }
}
