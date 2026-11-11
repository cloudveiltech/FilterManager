<?php

use App\Client\PlainTextFilteringListType;
use App\Services\FilterCheck\AbpRule;
use App\Services\FilterCheck\AbpRuleMatcher;
use App\Services\FilterCheck\AbpRuleParser;
use App\Services\FilterCheck\FilterCategory;
use App\Services\FilterCheck\RuleTooComplexException;

// Expected results follow github.com/patriciy/adblock (rules.go) and goproxy-managed,
// the matcher both CloudVeil apps use.

function ruleMatches(string $line, string $url, string $referer = ''): bool
{
    $rule = (new AbpRuleParser)->parse($line);
    expect($rule)->not->toBeNull();

    if ($rule->isDomainRule()) {
        $category = new FilterCategory('/t', 't', PlainTextFilteringListType::Blacklist, FilterCategory::SOURCE_GROUP);
        $category->add($rule);

        return $category->match(new AbpRuleMatcher, $url, parse_url($url, PHP_URL_HOST), $referer) !== null;
    }

    return (new AbpRuleMatcher)->matches($rule, $url, $referer);
}

test('blank lines, comments, cosmetic rules and rejected rules are skipped', function (string $line) {
    expect((new AbpRuleParser)->parse($line))->toBeNull();
})->with(['', '   ', '! comment', 'example.com##.ad', 'example.com#?#.ad', '||', '||*.example.com']);

test('a plain ||domain rule goes into the domain map', function () {
    $rule = (new AbpRuleParser)->parse('  ||Example.COM  ');

    expect($rule->isDomainRule())->toBeTrue()
        ->and($rule->domainKey)->toBe('example.com')
        ->and($rule->exception)->toBeFalse()
        ->and($rule->raw)->toBe('||example.com');
});

test('the domain map matches the domain and its subdomains only', function (string $url, bool $expected) {
    expect(ruleMatches('||example.com', $url))->toBe($expected);
})->with([
    ['https://example.com/', true],
    ['https://www.example.com/page', true],
    ['https://a.b.example.com/', true],
    ['https://notexample.com/', false],
    ['https://example.com.au/', false],
]);

test('a ||domain/path rule lands in the domain map and never matches', function () {
    $rule = (new AbpRuleParser)->parse('||example.com/page');

    expect($rule->domainKey)->toBe('example.com/page')
        ->and(ruleMatches('||example.com/page', 'https://example.com/page'))->toBeFalse();
});

test('domain rules with a separator and path match by URL prefix', function (string $url, bool $expected) {
    expect(ruleMatches('||www.wdrb.com^news^louisville', $url))->toBe($expected);
})->with([
    ['https://www.wdrb.com/news/louisville-handyman', true],
    ['http://www.wdrb.com:8080/news/louisville', true],
    ['https://www.wdrb.com/sports/louisville', false],
    ['https://www.wdrb.com/newsy/louisville', false],
]);

test('a separator matches one non-word character or the end of the URL', function (string $url, bool $expected) {
    expect(ruleMatches('||example.com^', $url))->toBe($expected);
})->with([
    ['https://example.com', true],
    ['https://example.com/', true],
    ['https://example.com?q=1', false],
    ['https://sub.example.com/x', true],
]);

test('a rule without an anchor matches anywhere in the URL', function (string $url, bool $expected) {
    expect(ruleMatches('example.com', $url))->toBe($expected);
})->with([
    ['https://example.com/', true],
    ['https://notexample.com/', true],
    ['https://other.org/?u=example.com', true],
    ['https://example.org/', false],
]);

test('a start anchor matches from the start; an end anchor is only a prefix match', function (string $url, bool $expected) {
    expect(ruleMatches('|https://foo.com|', $url))->toBe($expected);
})->with([
    ['https://foo.com', true],
    ['https://foo.com/more', true],
    ['http://bar.com/?https://foo.com', false],
]);

test('wildcards match any run of characters', function (string $url, bool $expected) {
    expect(ruleMatches('||example.com^*/ads/', $url))->toBe($expected);
})->with([
    ['https://example.com/a/b/ads/x', true],
    ['https://example.com/ads/', false],
    ['https://example.com/a/b/adsx', false],
]);

test('options other than referer are dropped, so the rule always applies', function () {
    expect(ruleMatches('||ads.com^$third-party,image', 'https://ads.com/banner.png'))->toBeTrue();
});

test('referer options need a matching referer, or a non-matching one when negated', function () {
    expect(ruleMatches('||cdn.com^$referer=site.com', 'https://cdn.com/x', 'https://site.com/page'))->toBeTrue()
        ->and(ruleMatches('||cdn.com^$referer=site.com', 'https://cdn.com/x', 'https://other.com/'))->toBeFalse()
        ->and(ruleMatches('||cdn.com^$referer=site.com', 'https://cdn.com/x'))->toBeFalse()
        ->and(ruleMatches('||cdn.com^$referer=~site.com', 'https://cdn.com/x'))->toBeTrue()
        ->and(ruleMatches('||cdn.com^$referer=~site.com', 'https://cdn.com/x', 'https://site.com/'))->toBeFalse();
});

test('@@ rules are exceptions, and the Mac allow-list flag turns any rule into one', function () {
    $parser = new AbpRuleParser;

    expect($parser->parse('@@||example.com^')->exception)->toBeTrue()
        ->and($parser->parse('||example.com', forceException: true)->exception)->toBeTrue()
        ->and($parser->parse('@@||example.com', forceException: true)->raw)->toBe('@@||example.com');
});

test('a list checks @@ rules, then other rules, then the domain map', function () {
    $parser = new AbpRuleParser;
    $category = new FilterCategory('/t', 't', PlainTextFilteringListType::Blacklist, FilterCategory::SOURCE_GROUP);
    $category->add($parser->parse('||example.com'));
    $category->add($parser->parse('||example.com^bad'));
    $category->add($parser->parse('@@||example.com^bad^ok'));
    $matcher = new AbpRuleMatcher;

    expect($category->match($matcher, 'https://example.com/bad/ok', 'example.com')->rule->raw)->toBe('@@||example.com^bad^ok')
        ->and($category->match($matcher, 'https://example.com/bad/x', 'example.com')->rule->raw)->toBe('||example.com^bad')
        ->and($category->match($matcher, 'https://example.com/', 'example.com')->rule->raw)->toBe('||example.com')
        ->and($category->match($matcher, 'https://localhost/', 'localhost'))->toBeNull();
});

test('the domain map is walked from the shortest domain, and later lines replace earlier ones', function () {
    $parser = new AbpRuleParser;
    $category = new FilterCategory('/t', 't', PlainTextFilteringListType::Blacklist, FilterCategory::SOURCE_GROUP);
    $category->add($parser->parse('@@||www.example.com'));
    $category->add($parser->parse('@@||example.com'));
    $category->add($parser->parse('||example.com'));

    $match = $category->match(new AbpRuleMatcher, 'https://www.example.com/', 'www.example.com');

    expect($match->rule->raw)->toBe('||example.com')
        ->and($match->rule->exception)->toBeFalse();
});

test('domain anchors strip the scheme and port and need http(s)', function () {
    expect(AbpRuleMatcher::matchDomainAnchor('https://example.com:443/x', 'example.com'))->toBe('/x')
        ->and(AbpRuleMatcher::matchDomainAnchor('http://example.com', 'example.com'))->toBe('')
        ->and(AbpRuleMatcher::matchDomainAnchor('ftp://example.com/', 'example.com'))->toBeNull();
});

test('rule parts are rewritten into the client chain', function () {
    expect((new AbpRuleParser)->parse('||example.com^*/ads/')->parts)->toBe([
        [AbpRule::DOMAIN_ANCHOR, 'example.com'],
        [AbpRule::SEPARATOR, '^'],
        [AbpRule::SUBSTRING, '/ads/'],
        [AbpRule::WILDCARD, ''],
    ]);
});

test('wildcards in a row are merged into one', function () {
    $parser = new AbpRuleParser;

    expect($parser->parse('**********zzz')->parts)->toBe($parser->parse('*zzz')->parts)
        ->and($parser->parse('a***^**b')->parts)->toBe($parser->parse('a*^*b')->parts)
        ->and(ruleMatches('ads***banner', 'https://example.com/ads/x/banner.png'))->toBeTrue();
});

test('a rule full of wildcards gives up quickly instead of hanging', function () {
    $started = microtime(true);

    expect(fn () => (new AbpRuleMatcher)->matches((new AbpRuleParser)->parse('*a*a*a*a*a*a*a*a*a*a*a*a*zzz'), 'https://example.org/'.str_repeat('a', 2000)))
        ->toThrow(RuleTooComplexException::class)
        ->and(microtime(true) - $started)->toBeLessThan(3.0)
        // The same kind of rule on an ordinary-length URL is still checked.
        ->and((new AbpRuleMatcher)->matches((new AbpRuleParser)->parse('*^*^*^*^zzz'), 'https://www.example.com/'.str_repeat('ab/', 60)))->toBeFalse();
});

test('a rule that needs more than the step limit stops with an error', function () {
    $rule = (new AbpRuleParser)->parse('*a*a*a*zzz');

    expect(fn () => (new AbpRuleMatcher(maxSteps: 50))->matches($rule, 'https://example.org/'.str_repeat('a', 80)))
        ->toThrow(RuleTooComplexException::class, 'A rule is too complex to check: *a*a*a*zzz.');
});

test('normal rules match on long URLs', function () {
    $matcher = new AbpRuleMatcher;
    $url = 'https://www.example.com/'.str_repeat('path/', 200).'?'.str_repeat('q=1&', 200);

    expect($matcher->matches((new AbpRuleParser)->parse('/path/*q=1^'), $url))->toBeTrue()
        ->and($matcher->matches((new AbpRuleParser)->parse('||example.com^*/ads/*banner'), 'https://www.example.com/'.str_repeat('x/', 900).'ads/x/banner'))->toBeTrue();
});
