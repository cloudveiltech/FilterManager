<?php

namespace App\Services\FilterCheck;

/**
 * Matches a parsed rule against a lowercased URL, following the node dispatch in
 * github.com/patriciy/adblock (adblock/rules.go). The client builds a tree of
 * many rules; matching each rule's chain on its own gives the same answer and
 * tells us which rule matched.
 */
class AbpRuleMatcher
{
    /**
     * How many steps one rule may take against one URL, a fraction of a second
     * of work. Normal rules need a few hundred at most.
     */
    public const MAX_STEPS = 1_000_000;

    private int $steps = 0;

    /**
     * Rule positions already known not to match, keyed by part index and how
     * much of the URL was left. The remaining URL is always a suffix of the
     * original, so its length identifies it. Without this, a rule with many
     * wildcards retries the same positions an exponential number of times.
     *
     * @var array<string, true>
     */
    private array $failed = [];

    private AbpRule $rule;

    public function __construct(private readonly int $maxSteps = self::MAX_STEPS) {}

    /**
     * @throws RuleTooComplexException When the rule needs more than the step limit.
     */
    public function matches(AbpRule $rule, string $url, string $referer = ''): bool
    {
        if ($rule->parts === []) {
            return false;
        }

        $this->steps = 0;
        $this->failed = [];
        $this->rule = $rule;

        return $this->dispatch($rule->parts, 0, $url, $referer);
    }

    /**
     * Strip the scheme and port and test the host against a `||` domain. Returns
     * the rest of the URL (from its first `/`) on a match, or null.
     */
    public static function matchDomainAnchor(string $url, string $expectedDomain): ?string
    {
        if (! str_starts_with($url, 'http')) {
            return null;
        }
        $s = substr($url, 4);
        if ($s !== '' && $s[0] === 's') {
            $s = substr($s, 1);
        }
        if (! str_starts_with($s, '://')) {
            return null;
        }
        $s = substr($s, 3);

        $slash = strpos($s, '/');
        $domain = $slash === false ? $s : substr($s, 0, $slash);
        $rest = $slash === false ? '' : substr($s, $slash);

        for ($i = strlen($domain); $i > 0; $i--) {
            $c = $domain[$i - 1];
            if (ctype_digit($c)) {
                continue;
            }
            if ($c === ':') {
                $domain = substr($domain, 0, $i - 1);
            }
            break;
        }

        if ($domain === $expectedDomain) {
            return $rest;
        }

        $length = strlen($domain);
        $expectedLength = strlen($expectedDomain);
        if ($length > $expectedLength
            && str_ends_with($domain, $expectedDomain)
            && $domain[$length - $expectedLength - 1] === '.') {
            return $rest;
        }

        return null;
    }

    /**
     * A node with nothing after it is a complete match.
     *
     * @param  array<int, array{0: string, 1: string}>  $parts
     */
    private function next(array $parts, int $index, string $url, string $referer): bool
    {
        if ($index >= count($parts)) {
            return true;
        }

        if (++$this->steps > $this->maxSteps) {
            throw new RuleTooComplexException($this->rule);
        }

        $key = $index.':'.strlen($url);
        if (isset($this->failed[$key])) {
            return false;
        }

        if ($this->dispatch($parts, $index, $url, $referer)) {
            return true;
        }
        $this->failed[$key] = true;

        return false;
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $parts
     */
    private function dispatch(array $parts, int $index, string $url, string $referer): bool
    {
        [$type, $value] = $parts[$index];

        switch ($type) {
            case AbpRule::EXACT:
                if (! str_starts_with($url, $value)) {
                    return false;
                }

                return $this->next($parts, $index + 1, substr($url, strlen($value)), $referer);

            case AbpRule::SEPARATOR:
                if (! preg_match('/^(?:[^\w\d_\-.%]|\z)/', $url, $m)) {
                    return false;
                }

                return $this->next($parts, $index + 1, substr($url, strlen($m[0])), $referer);

            case AbpRule::WILDCARD:
                if ($index === count($parts) - 1) {
                    return true;
                }
                if ($url === '') {
                    return $this->next($parts, $index + 1, $url, $referer);
                }
                // Like the client, this never tries the empty remainder.
                for ($i = 0, $length = strlen($url); $i < $length; $i++) {
                    if ($this->next($parts, $index + 1, substr($url, $i), $referer)) {
                        return true;
                    }
                }

                return false;

            case AbpRule::DOMAIN_ANCHOR:
                $rest = self::matchDomainAnchor($url, $value);

                return $rest !== null && $this->next($parts, $index + 1, $rest, $referer);

            case AbpRule::SUBSTRING:
                while ($url !== '') {
                    $pos = strpos($url, $value);
                    if ($pos === false) {
                        break;
                    }
                    $url = substr($url, $pos + strlen($value));
                    if ($this->next($parts, $index + 1, $url, $referer)) {
                        return true;
                    }
                }

                return false;

            case AbpRule::REFERER:
                return $this->matchReferer($value, $referer);
        }

        return false;
    }

    private function matchReferer(string $referers, string $referer): bool
    {
        $inverse = false;
        if (str_starts_with($referers, '~')) {
            $referers = substr($referers, 1);
            $inverse = true;
        }

        if ($referer === '') {
            return $inverse;
        }

        $matchedAny = false;
        foreach (explode('|', $referers) as $domain) {
            if (self::matchDomainAnchor($referer, $domain) !== null) {
                if (! $inverse) {
                    return true;
                }
                $matchedAny = true;
            }
        }

        return $inverse && ! $matchedAny;
    }
}
