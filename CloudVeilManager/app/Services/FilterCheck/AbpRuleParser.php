<?php

namespace App\Services\FilterCheck;

/**
 * Parses rule lines the way the client filters do.
 *
 * This is a port of `ParseRule` and the `RuleTree.AddRule` rewrites from
 * github.com/patriciy/adblock (adblock/rules.go), plus the domain-map shortcut in
 * goproxy-managed's `AdBlockMatcher.AddRule`. Its quirks are kept on purpose:
 * `$` options other than `referer=` are dropped, regex and hosts-file lines are
 * plain text, and `||example.com/path` lands in the domain map where it never
 * matches.
 */
class AbpRuleParser
{
    /**
     * Parse one list line. Returns null for blank lines, comments, cosmetic
     * rules and rules the clients reject.
     *
     * @param  bool  $forceException  Treat the rule as `@@` (the Mac client does this for whitelists).
     */
    public function parse(string $line, bool $forceException = false): ?AbpRule
    {
        $s = trim(strtolower($line));
        $raw = $s;

        if ($forceException && ! str_starts_with($s, '@@')) {
            $s = '@@'.$s;
        }

        if ($s === '' || $s[0] === '!') {
            return null;
        }
        if (str_contains($s, '##') || str_contains($s, '#?#')) {
            return null;
        }

        $exception = false;
        if (str_starts_with($s, '@@')) {
            $exception = true;
            $s = substr($s, 2);
        }

        $parts = [];
        if (str_starts_with($s, '||')) {
            $parts[] = [AbpRule::DOMAIN_ANCHOR, '||'];
            $s = substr($s, 2);
        }

        $opts = '';
        $dollar = strrpos($s, '$');
        if ($dollar !== false) {
            $opts = substr($s, $dollar);
            $s = substr($s, 0, $dollar);
        }

        while ($s !== '') {
            $pos = strcspn($s, '*^|');
            if ($pos === strlen($s)) {
                $parts[] = [AbpRule::EXACT, $s];
                break;
            }
            if ($pos > 0) {
                $parts[] = [AbpRule::EXACT, substr($s, 0, $pos)];
            }
            $parts[] = [match ($s[$pos]) {
                '*' => AbpRule::WILDCARD,
                '^' => AbpRule::SEPARATOR,
                '|' => AbpRule::START_ANCHOR,
            }, $s[$pos]];
            $s = substr($s, $pos + 1);
        }

        if ($opts !== '') {
            $refererPos = strpos($opts, 'referer=');
            if ($refererPos !== false) {
                $parts[] = [AbpRule::REFERER, substr($opts, $refererPos + strlen('referer='))];
            }
        }

        // goproxy-managed keeps `||domain` rules in a domain map instead of the tree.
        if (count($parts) === 2 && $parts[0][0] === AbpRule::DOMAIN_ANCHOR && $parts[1][0] === AbpRule::EXACT) {
            return new AbpRule($raw, $exception, $parts[1][1]);
        }

        $parts = $this->rewriteDomainAnchors($parts);
        if ($parts === null) {
            return null;
        }
        $parts = $this->replaceWildcardWithSubstring($this->collapseWildcards($this->addLeadingTrailingWildcards($parts)));

        if ($parts === []) {
            return null;
        }

        return new AbpRule($raw, $exception, null, $parts);
    }

    /**
     * Fold `||` and the following domain into one domain-anchor part.
     *
     * @param  array<int, array{0: string, 1: string}>  $parts
     * @return array<int, array{0: string, 1: string}>|null Null when the client would reject the rule.
     */
    private function rewriteDomainAnchors(array $parts): ?array
    {
        $hasAnchor = false;
        $rewritten = [];

        foreach ($parts as $i => $part) {
            if ($part[0] === AbpRule::DOMAIN_ANCHOR) {
                if ($i !== 0 || count($parts) < 2 || $parts[1][0] !== AbpRule::EXACT) {
                    return null;
                }
                $hasAnchor = true;
            } elseif ($part[0] === AbpRule::EXACT && $hasAnchor) {
                $value = $part[1];
                $slash = strpos($value, '/');
                $domain = $slash === false ? $value : substr($value, 0, $slash);
                $rest = $slash === false ? '' : substr($value, $slash);

                $rewritten[count($rewritten) - 1] = [AbpRule::DOMAIN_ANCHOR, $domain];
                if ($rest !== '') {
                    $rewritten[] = [AbpRule::EXACT, $rest];
                }
                $hasAnchor = false;

                continue;
            }

            $rewritten[] = $part;
        }

        return $rewritten;
    }

    /**
     * Add the implied leading and trailing wildcards; anchors at either end
     * remove them, and a `|` in the middle is a literal pipe.
     *
     * @param  array<int, array{0: string, 1: string}>  $parts
     * @return array<int, array{0: string, 1: string}>
     */
    private function addLeadingTrailingWildcards(array $parts): array
    {
        $rewritten = [];
        $last = count($parts) - 1;

        foreach ($parts as $i => $part) {
            if ($i === 0 && $part[0] !== AbpRule::START_ANCHOR && $part[0] !== AbpRule::DOMAIN_ANCHOR) {
                $rewritten[] = [AbpRule::WILDCARD, ''];
            }

            if ($part[0] === AbpRule::START_ANCHOR) {
                if ($i !== 0 && $i !== $last) {
                    $rewritten[] = [AbpRule::EXACT, '|'];
                }
            } else {
                $rewritten[] = $part;
            }

            if ($i === $last && $part[0] !== AbpRule::START_ANCHOR) {
                $rewritten[] = [AbpRule::WILDCARD, ''];
            }
        }

        return $rewritten;
    }

    /**
     * Merge wildcards that follow each other. `**` matches the same as `*`, but
     * each extra wildcard multiplies the work a failed match does.
     *
     * @param  array<int, array{0: string, 1: string}>  $parts
     * @return array<int, array{0: string, 1: string}>
     */
    private function collapseWildcards(array $parts): array
    {
        $rewritten = [];

        foreach ($parts as $part) {
            if ($part[0] === AbpRule::WILDCARD && end($rewritten) !== false && end($rewritten)[0] === AbpRule::WILDCARD) {
                continue;
            }
            $rewritten[] = $part;
        }

        return $rewritten;
    }

    /**
     * Turn each wildcard followed by exact text into one substring search.
     *
     * @param  array<int, array{0: string, 1: string}>  $parts
     * @return array<int, array{0: string, 1: string}>
     */
    private function replaceWildcardWithSubstring(array $parts): array
    {
        $rewritten = [];

        foreach ($parts as $i => $part) {
            if ($i > 0 && $parts[$i - 1][0] === AbpRule::WILDCARD && $part[0] === AbpRule::EXACT) {
                $rewritten[count($rewritten) - 1] = [AbpRule::SUBSTRING, $part[1]];

                continue;
            }
            $rewritten[] = $part;
        }

        return $rewritten;
    }
}
