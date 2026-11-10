<?php

namespace App\Services\FilterCheck;

use App\Client\PlainTextFilteringListType;
use App\Models\AppUserActivation;
use App\Models\FilterRulesManager;
use App\Models\User;
use App\Services\ClientConfigurationBuilder;
use RuntimeException;

/**
 * Works out whether URLs would be blocked for a device, user or group, the way
 * the CloudVeil apps decide it.
 *
 * The decision follows the Windows app: an allow list or any `@@` rule that
 * matches allows the URL; otherwise the first block list that matches blocks
 * it; bypass lists count only when nothing else matched and bypass is off. The
 * Mac app checks allow lists first and stops at the first match, so its answer
 * is worked out too and flagged when it differs.
 *
 * Not covered: DNS filtering, page text (trigger words), image filtering,
 * blocked apps, the block threshold and time restrictions (results are what
 * the apps do when no time restriction is in effect).
 */
class FilterChecker
{
    private const CUSTOM_WHITELIST = '/user/custom_whitelist';

    private const CUSTOM_BYPASS = '/user/custom_bypass';

    private const SELF_MODERATION = '/user/self_moderation';

    public function __construct(
        private readonly ClientConfigurationBuilder $configurationBuilder,
        private readonly CandidateRuleFinder $finder,
        private readonly AbpRuleParser $parser,
        private readonly AbpRuleMatcher $matcher,
        private readonly FilterRulesManager $rulesManager,
    ) {}

    /**
     * @param  array<int, string>  $urls
     * @return array<string, mixed>
     */
    public function check(FilterTarget $target, array $urls, bool $bypassActive = false): array
    {
        $configuration = $this->configurationBuilder->build(
            $target->group,
            $target->user ?? new User,
            $target->activation ?? new AppUserActivation,
        );
        if ($configuration === null) {
            throw new RuntimeException('No configuration could be built for this target.');
        }

        $requests = array_map(fn (string $url) => $this->normalize($url), $urls);
        $hosts = array_values(array_unique(array_filter(array_column($requests, 'host'))));

        [$lists, $missing] = $this->configuredLists($configuration);
        $candidates = $this->finder->find(array_values(array_filter(array_column($lists, 'file'))), $hosts);

        $windows = $this->windowsCategories($lists, $candidates, $configuration);
        $mac = $this->macCategories($lists, $candidates, $configuration);

        $results = [];
        foreach ($requests as $request) {
            if ($request['host'] === null) {
                $results[] = ['url' => $request['input'], 'error' => 'This is not a valid URL.'];

                continue;
            }

            try {
                $results[] = $this->checkUrl($request, $windows, $mac, $bypassActive);
            } catch (RuleTooComplexException $e) {
                $results[] = ['url' => $request['input'], 'error' => $e->getMessage()];
            }
        }

        $notes = ['DNS filtering, page text (trigger words), image filtering, blocked apps and time restrictions are not checked.'];
        foreach ($missing as $list) {
            $notes[] = "The list file for {$list} is missing on this server, so it was not checked.";
        }

        return [
            'target' => $target->toArray(),
            'bypass_active' => $bypassActive,
            'results' => $results,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array{input: string, url: string, host: string}  $request
     * @param  array{main: array<int, FilterCategory>, bypass: array<int, FilterCategory>}  $windows
     * @param  array{whitelist: array<int, FilterCategory>, blacklist: array<int, FilterCategory>, bypass: array<int, FilterCategory>}  $mac
     * @return array<string, mixed>
     */
    private function checkUrl(array $request, array $windows, array $mac, bool $bypassActive): array
    {
        $url = $request['url'];
        $host = $request['host'];

        // The apps don't decrypt a host that is allowed outright, so nothing else applies to it.
        $hostAllow = $this->firstAllow($this->windowsMatches($windows, 'https://'.$host, $host, $bypassActive));
        $matches = $this->windowsMatches($windows, $url, $host, $bypassActive);

        if ($hostAllow !== null) {
            [$blocked, $decidedBy] = [false, $hostAllow];
        } elseif (($allow = $this->firstAllow($matches)) !== null) {
            [$blocked, $decidedBy] = [false, $allow];
        } else {
            [$blocked, $decidedBy] = [$matches !== [], $matches[0] ?? null];
        }

        $verdict = match (true) {
            ! $blocked => 'allowed',
            $decidedBy->category->isBypass() => 'blocked_bypassable',
            default => 'blocked',
        };

        $macBlocked = $this->macBlocked($mac, $url, $host, $bypassActive);

        // Every list that matches, whatever wins, so support can see all the reasons.
        $trace = [];
        foreach ([...$windows['main'], ...$windows['bypass']] as $category) {
            $match = $category->match($this->matcher, $url, $host);
            if ($match !== null) {
                $trace[] = $match;
            }
        }

        return [
            'url' => $request['input'],
            'checked_url' => $url,
            'blocked' => $blocked,
            'verdict' => $verdict,
            'decided_by' => $decidedBy?->toArray(),
            'host_passthrough' => $hostAllow !== null,
            'blocking_categories' => array_values(array_unique(array_map(
                fn (CategoryMatch $match) => $match->category->category,
                array_filter($trace, fn (CategoryMatch $match) => ! $match->allows()),
            ))),
            'matches' => array_map(fn (CategoryMatch $match) => $match->toArray(), $trace),
            'mac' => [
                'blocked' => $macBlocked,
                'differs' => $macBlocked !== $blocked,
            ],
        ];
    }

    /**
     * What the Windows app's matcher returns: every block and allow list that
     * matches, or failing that every bypass list (unless bypass is active).
     *
     * @param  array{main: array<int, FilterCategory>, bypass: array<int, FilterCategory>}  $categories
     * @return array<int, CategoryMatch>
     */
    private function windowsMatches(array $categories, string $url, string $host, bool $bypassActive): array
    {
        $matches = $this->matchAll($categories['main'], $url, $host);
        if ($matches !== [] || $bypassActive) {
            return $matches;
        }

        return $this->matchAll($categories['bypass'], $url, $host);
    }

    /**
     * The Mac app stops at the first matching list: allow lists, then block
     * lists, then bypass lists. An `@@` match allows.
     *
     * @param  array{whitelist: array<int, FilterCategory>, blacklist: array<int, FilterCategory>, bypass: array<int, FilterCategory>}  $categories
     */
    private function macBlocked(array $categories, string $url, string $host, bool $bypassActive): bool
    {
        $first = function (string $url) use ($categories, $host, $bypassActive): ?CategoryMatch {
            $groups = $bypassActive ? ['whitelist', 'blacklist'] : ['whitelist', 'blacklist', 'bypass'];
            foreach ($groups as $group) {
                foreach ($categories[$group] as $category) {
                    $match = $category->match($this->matcher, $url, $host);
                    if ($match !== null) {
                        return $match;
                    }
                }
            }

            return null;
        };

        if ($first('https://'.$host)?->rule->exception) {
            return false;
        }

        $match = $first($url);

        return $match !== null && ! $match->rule->exception;
    }

    /**
     * @param  array<int, FilterCategory>  $categories
     * @return array<int, CategoryMatch>
     */
    private function matchAll(array $categories, string $url, string $host): array
    {
        $matches = [];
        foreach ($categories as $category) {
            $match = $category->match($this->matcher, $url, $host);
            if ($match !== null) {
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * @param  array<int, CategoryMatch>  $matches
     */
    private function firstAllow(array $matches): ?CategoryMatch
    {
        foreach ($matches as $match) {
            if ($match->allows()) {
                return $match;
            }
        }

        return null;
    }

    /**
     * The URL lists in the configuration, in the order the apps load them.
     *
     * @param  array<string, mixed>  $configuration
     * @return array{0: array<int, array{path: string, category: string, type: string, file: string|null}>, 1: array<int, string>}
     */
    private function configuredLists(array $configuration): array
    {
        $lists = [];
        $missing = [];

        foreach ($configuration['ConfiguredLists'] ?? [] as $list) {
            $list = (array) $list;
            $type = $list['ListType'] ?? null;
            $path = (string) ($list['RelativeListPath'] ?? '');
            if ($type === PlainTextFilteringListType::TextTrigger || $path === '') {
                continue;
            }

            $segments = explode('/', trim($path, '/'));
            if (count($segments) !== 3) {
                $missing[] = $path;

                continue;
            }
            [$namespace, $category, $fileName] = $segments;

            $file = $this->rulesManager->rulesetFilePath($namespace, $category, pathinfo($fileName, PATHINFO_FILENAME));
            if (! is_file($file)) {
                $missing[] = $path;
                $file = null;
            }

            $lists[] = ['path' => $path, 'category' => $category, 'type' => $type, 'file' => $file];
        }

        return [$lists, $missing];
    }

    /**
     * Windows loads the group's lists, then the custom allow, bypass and block
     * lists, with custom entries used exactly as written.
     *
     * @param  array<int, array{path: string, category: string, type: string, file: string|null}>  $lists
     * @param  array<string, array<int, string>>  $candidates
     * @param  array<string, mixed>  $configuration
     * @return array{main: array<int, FilterCategory>, bypass: array<int, FilterCategory>}
     */
    private function windowsCategories(array $lists, array $candidates, array $configuration): array
    {
        $categories = [];
        foreach ($lists as $list) {
            $categories[] = $this->category($list['path'], $list['category'], $list['type'], FilterCategory::SOURCE_GROUP,
                $candidates[$list['file']] ?? []);
        }

        $custom = [
            [self::CUSTOM_WHITELIST, 'CustomWhitelist', PlainTextFilteringListType::Whitelist],
            [self::CUSTOM_BYPASS, 'CustomBypasslist', PlainTextFilteringListType::BypassList],
            [self::SELF_MODERATION, 'SelfModeration', PlainTextFilteringListType::Blacklist],
        ];
        foreach ($custom as [$path, $key, $type]) {
            $categories[] = $this->category($path, basename($path), $type, FilterCategory::SOURCE_CUSTOM,
                $this->entries($configuration, $key));
        }

        return [
            'main' => array_values(array_filter($categories, fn (FilterCategory $category) => ! $category->isBypass())),
            'bypass' => array_values(array_filter($categories, fn (FilterCategory $category) => $category->isBypass())),
        ];
    }

    /**
     * The Mac app turns every allow-list line into an `@@` rule, puts `||` in
     * front of custom entries, and has no custom bypass list.
     *
     * @param  array<int, array{path: string, category: string, type: string, file: string|null}>  $lists
     * @param  array<string, array<int, string>>  $candidates
     * @param  array<string, mixed>  $configuration
     * @return array{whitelist: array<int, FilterCategory>, blacklist: array<int, FilterCategory>, bypass: array<int, FilterCategory>}
     */
    private function macCategories(array $lists, array $candidates, array $configuration): array
    {
        $groups = ['whitelist' => [], 'blacklist' => [], 'bypass' => []];
        $groupFor = [
            PlainTextFilteringListType::Whitelist => 'whitelist',
            PlainTextFilteringListType::Blacklist => 'blacklist',
            PlainTextFilteringListType::BypassList => 'bypass',
        ];

        foreach ($lists as $list) {
            if (! isset($groupFor[$list['type']])) {
                continue;
            }
            $groups[$groupFor[$list['type']]][] = $this->category($list['path'], $list['category'], $list['type'],
                FilterCategory::SOURCE_GROUP, $candidates[$list['file']] ?? [],
                $list['type'] === PlainTextFilteringListType::Whitelist);
        }

        $prefix = fn (array $entries) => array_map(fn (string $entry) => '||'.$entry, $entries);
        $groups['whitelist'][] = $this->category(self::CUSTOM_WHITELIST, 'custom_whitelist', PlainTextFilteringListType::Whitelist,
            FilterCategory::SOURCE_CUSTOM, $prefix($this->entries($configuration, 'CustomWhitelist')), true);
        $groups['blacklist'][] = $this->category(self::SELF_MODERATION, 'self_moderation', PlainTextFilteringListType::Blacklist,
            FilterCategory::SOURCE_CUSTOM, $prefix($this->entries($configuration, 'SelfModeration')));

        return $groups;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function category(string $path, string $name, string $type, string $source, array $lines, bool $forceException = false): FilterCategory
    {
        $category = new FilterCategory($path, $name, $type, $source);

        foreach ($lines as $line) {
            $rule = $this->parser->parse($line, $forceException);
            if ($rule !== null) {
                $category->add($rule);
            }
        }

        return $category;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<int, string>
     */
    private function entries(array $configuration, string $key): array
    {
        return array_values(array_filter(
            array_map('strval', (array) ($configuration[$key] ?? [])),
            fn (string $entry) => $entry !== '',
        ));
    }

    /**
     * Lowercase the URL, add https:// when no scheme is given, give it a path
     * and drop any #fragment, as the apps see a request.
     *
     * @return array{input: string, url: string, host: string|null}
     */
    private function normalize(string $input): array
    {
        $url = strtolower(trim($input));
        if (! str_contains($url, '://')) {
            $url = 'https://'.$url;
        }
        $url = explode('#', $url, 2)[0];

        $parts = parse_url($url);
        $host = $parts['host'] ?? null;
        if (! is_string($host) || $host === '' || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return ['input' => $input, 'url' => $url, 'host' => null];
        }

        if (! isset($parts['path'])) {
            $authorityEnd = strpos($url, '://') + 3;
            $pathStart = strcspn($url, '?', $authorityEnd) + $authorityEnd;
            $url = substr($url, 0, $pathStart).'/'.substr($url, $pathStart);
        }

        return ['input' => $input, 'url' => $url, 'host' => trim($host, '[]')];
    }
}
