<?php

namespace App\Services\FilterCheck;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Finds the rules in list files that could apply to a set of hosts, using grep
 * instead of reading every list into PHP.
 *
 * A `||domain` rule can only match a URL whose host is that domain or one of its
 * subdomains, so each check searches only for lines naming one of the hosts'
 * domain suffixes. Other lines ("loose" lines: rules without `||`, lines with
 * leading spaces or capital letters) could match anything, so they are always
 * included. They are rare, so they are found with one scan per version of a
 * list file and cached. The matcher then decides which candidates really match.
 */
class CandidateRuleFinder
{
    /**
     * A line the domain search could miss.
     */
    private const LOOSE_LINE = '^([^|@!]|[|][^|]|@[^@]|@@[^|]|@@[|][^|])|[A-Z]';

    /**
     * @param  array<int, string>  $files  Absolute paths of existing list files.
     * @param  array<int, string>  $hosts  Lowercased hosts being checked.
     * @return array<string, array<int, string>> Candidate lines, keyed by file path.
     */
    public function find(array $files, array $hosts): array
    {
        $domains = $this->domainSuffixes($hosts);
        if ($files === [] || $domains === []) {
            return [];
        }

        $alternatives = implode('|', array_map(fn (string $domain) => $this->escape($domain), $domains));
        $anchored = $this->grep(['-e', '^(@@)?[|][|]('.$alternatives.')([*^|$/[:space:]]|$)'], $files);

        $candidates = [];
        foreach ($this->looseLines($files) as $file => $lines) {
            $candidates[$file] = [...$lines, ...($anchored[$file] ?? [])];
        }
        foreach ($anchored as $file => $lines) {
            $candidates[$file] ??= $lines;
        }

        return $candidates;
    }

    /**
     * Every domain a `||` rule could name and still match one of the hosts:
     * the host itself and each parent domain, down to the top-level domain.
     *
     * @param  array<int, string>  $hosts
     * @return array<int, string>
     */
    public function domainSuffixes(array $hosts): array
    {
        $domains = [];
        foreach ($hosts as $host) {
            $labels = explode('.', $host);
            for ($i = 0, $count = count($labels); $i < $count; $i++) {
                $domain = implode('.', array_slice($labels, $i));
                if ($domain !== '') {
                    $domains[$domain] = true;
                }
            }
        }

        return array_keys($domains);
    }

    /**
     * Escape a domain for a grep extended regular expression.
     */
    private function escape(string $domain): string
    {
        return preg_replace('/[.\[\]()*+?{}|^$\\\\]/', '\\\\$0', $domain);
    }

    /**
     * The loose lines of each file, cached until the file changes.
     *
     * @param  array<int, string>  $files
     * @return array<string, array<int, string>>
     */
    private function looseLines(array $files): array
    {
        $keys = [];
        foreach ($files as $file) {
            $keys[$file] = 'filter_check:loose:'.sha1($file.'|'.filesize($file).'|'.filemtime($file));
        }

        $cached = Cache::many(array_values($keys));
        $uncached = array_keys(array_filter($keys, fn (string $key) => ! is_array($cached[$key] ?? null)));

        $found = $uncached === [] ? [] : $this->grep(['-e', self::LOOSE_LINE], $uncached);

        $lines = [];
        foreach ($keys as $file => $key) {
            if (in_array($file, $uncached, true)) {
                $lines[$file] = $found[$file] ?? [];
                Cache::put($key, $lines[$file], now()->addDays(7));
            } else {
                $lines[$file] = $cached[$key];
            }
        }

        return array_filter($lines);
    }

    /**
     * Run grep over the files and group the matching lines by file.
     *
     * @param  array<int, string>  $patterns
     * @param  array<int, string>  $files
     * @return array<string, array<int, string>>
     */
    private function grep(array $patterns, array $files): array
    {
        $result = Process::timeout(config('filter_check.grep_timeout', 60))
            ->env(['LC_ALL' => 'C'])
            ->run([config('filter_check.grep_binary', 'grep'), '--null', '-H', '-E', ...$patterns, '--', ...$files]);

        // grep exits 1 when nothing matched; anything above that is an error.
        if ($result->exitCode() > 1) {
            throw new RuntimeException('Searching the filter lists failed: '.trim($result->errorOutput()));
        }

        $lines = [];
        foreach (explode("\n", $result->output()) as $line) {
            $separator = strpos($line, "\0");
            if ($separator !== false) {
                $lines[substr($line, 0, $separator)][] = substr($line, $separator + 1);
            }
        }

        return $lines;
    }
}
