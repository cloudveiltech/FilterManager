<?php

namespace App\Http\Controllers;

use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManageWhitelistController extends Controller
{
    /**
     * Update the custom whitelist (config_override.CustomWhitelist) of one user or one activation.
     *
     * Target: `identifier` picks an activation, otherwise `email` picks the user. Identifiers aren't
     * unique across users, so `email` alongside `identifier` narrows to that user's activation; a
     * match that is still ambiguous gets a 409 rather than a guess.
     *
     * Change: `domains` replaces the whole list (an empty array clears it), or `add` / `remove`
     * edit it in place. The client only handles domains, so every incoming entry is reduced to its
     * lowercase hostname (protocol, port, path and query dropped); entries without one are a 422.
     * An added domain already on the list is skipped. Entries already stored are kept verbatim and
     * only compared by their domain, since rewriting them could widen what they allow.
     *
     * Responds with the target and its resulting whitelist.
     */
    public function update(Request $request)
    {
        $entry = ['string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
            if (static::normalizeDomain($value) === null) {
                $fail('The :attribute field must be a domain, or a URL with one.');
            }
        }];

        $request->validate([
            'email' => ['required_without:identifier', 'nullable', 'string'],
            'identifier' => ['nullable', 'string'],
            // Key-presence rules rather than required/prohibits, which treat an empty array as
            // absent: `domains: []` is a deliberate "clear the list".
            'domains' => [Rule::when(!$request->hasAny(['add', 'remove']), 'present'), 'missing_with:add,remove', 'array'],
            'domains.*' => $entry,
            'add' => ['array'],
            'add.*' => $entry,
            'remove' => ['array'],
            'remove.*' => $entry,
        ]);

        $target = $request->filled('identifier')
            ? $this->findActivation($request->input('identifier'), $request->input('email'))
            : User::where('email', $request->input('email'))->first();

        if ($target === null) {
            return response()->json(['message' => 'No matching user or activation.'], 404);
        }
        if ($target === false) {
            return response()->json([
                'message' => 'More than one activation has this identifier. Pass the owner\'s email to pick one.',
            ], 409);
        }

        $domains = fn (string $key) => array_map(static::normalizeDomain(...), $request->input($key, []));

        if ($request->has('domains')) {
            $whitelist = array_values(array_unique($domains('domains')));
        } else {
            $remove = $domains('remove');
            // Stored entries may predate this endpoint (a URL, trailing whitespace), so match them by domain.
            $whitelist = array_values(array_filter(
                $target->config_override['CustomWhitelist'] ?? [],
                fn ($value) => !in_array(static::normalizeDomain((string) $value), $remove, true)
            ));
            // Only a stored entry that is a whole domain counts as already there: one with a path
            // (`facebook.com/marketplace`) doesn't cover the domain for a client that only reads domains.
            $present = array_map(fn ($value) => static::wholeDomain((string) $value), $whitelist);
            foreach ($domains('add') as $domain) {
                if (!in_array($domain, $present, true)) {
                    $whitelist[] = $domain;
                    $present[] = $domain;
                }
            }
        }

        $config = $target->config_override ?? [];
        if (empty($whitelist)) {
            unset($config['CustomWhitelist']);
        } else {
            $config['CustomWhitelist'] = $whitelist;
        }
        $target->config_override = $config;
        $target->save();

        return response()->json($this->describe($target) + ['whitelist' => $whitelist]);
    }

    /**
     * The lowercase hostname in a domain or URL (`https://Sub.Example.com/path` => `sub.example.com`),
     * or null when there isn't one. A leading `*.` or `.` wildcard is dropped.
     */
    private static function normalizeDomain(string $value): ?string
    {
        $host = parse_url(static::asUrl($value), PHP_URL_HOST);
        if (!is_string($host)) {
            return null;
        }

        $host = rtrim(preg_replace('/^(\*?\.)+/', '', strtolower($host)), '.');

        return str_contains($host, '.') && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ? $host
            : null;
    }

    /**
     * The trimmed value with a protocol, so parse_url reads a bare `example.com/path` as a host.
     */
    private static function asUrl(string $value): string
    {
        $value = trim($value);

        return preg_match('~^[a-z][a-z0-9+.-]*://~i', $value) ? $value : 'http://'.ltrim($value, '/');
    }

    /**
     * The entry's domain when it names a whole domain (no path or query), otherwise null.
     */
    private static function wholeDomain(string $value): ?string
    {
        $url = static::asUrl($value);

        return in_array(parse_url($url, PHP_URL_PATH), [null, '/'], true) && parse_url($url, PHP_URL_QUERY) === null
            ? static::normalizeDomain($value)
            : null;
    }

    /**
     * The activation with this identifier (narrowed to the email's user when given), null when none
     * match, or false when more than one does.
     */
    private function findActivation(string $identifier, ?string $email)
    {
        $activations = AppUserActivation::with('user:id,email')
            ->where('identifier', $identifier)
            ->when($email !== null && $email !== '', fn ($query) => $query->whereHas(
                'user', fn ($query) => $query->where('email', $email)
            ))
            ->limit(2)
            ->get();

        if ($activations->count() > 1) {
            return false;
        }

        return $activations->first();
    }

    private function describe(Model $target): array
    {
        if ($target instanceof AppUserActivation) {
            return [
                'type' => 'activation',
                'id' => $target->id,
                'identifier' => $target->identifier,
                'email' => $target->user?->email,
            ];
        }

        return ['type' => 'user', 'id' => $target->id, 'email' => $target->email];
    }
}
