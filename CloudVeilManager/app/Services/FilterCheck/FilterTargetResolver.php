<?php

namespace App\Services\FilterCheck;

use App\Models\AppUserActivation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Turns the target fields of a check (activation, user, group or none) into a
 * FilterTarget. A device uses its own group, else its user's group.
 */
class FilterTargetResolver
{
    public const TARGET_FIELDS = ['activation_id', 'identifier', 'user_id', 'email', 'group_id'];

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function resolve(array $input): FilterTarget
    {
        $given = array_values(array_filter(
            self::TARGET_FIELDS,
            fn (string $field) => isset($input[$field]) && $input[$field] !== '',
        ));

        if (count($given) > 1) {
            throw ValidationException::withMessages([
                $given[1] => 'Give only one of: '.implode(', ', self::TARGET_FIELDS).'.',
            ]);
        }

        return match ($given[0] ?? null) {
            'activation_id' => $this->forActivation(AppUserActivation::find($input['activation_id']), 'activation_id'),
            'identifier' => $this->forIdentifier((string) $input['identifier']),
            'user_id' => $this->forUser(User::find($input['user_id']), 'user_id'),
            'email' => $this->forUser(User::where('email', $input['email'])->first(), 'email'),
            'group_id' => $this->forGroup(Group::find($input['group_id']), FilterTarget::GROUP, 'group_id'),
            default => $this->forGroup(
                Group::find(config('filter_check.default_group_id')),
                FilterTarget::DEFAULT_GROUP,
                'group_id',
                'The default filter check group (filter_check.default_group_id) does not exist.',
            ),
        };
    }

    private function forIdentifier(string $identifier): FilterTarget
    {
        $activations = AppUserActivation::where('identifier', $identifier)->limit(2)->get();

        if ($activations->count() > 1) {
            throw ValidationException::withMessages([
                'identifier' => 'More than one activation has this identifier. Use activation_id instead.',
            ]);
        }

        return $this->forActivation($activations->first(), 'identifier');
    }

    private function forActivation(?AppUserActivation $activation, string $field): FilterTarget
    {
        $user = $activation?->user;
        if ($activation === null || $user === null) {
            throw ValidationException::withMessages([$field => 'No activation was found.']);
        }

        $group = $activation->group ?? $user->group;
        if ($group === null) {
            throw ValidationException::withMessages([$field => 'This activation and its user have no group.']);
        }

        return new FilterTarget(FilterTarget::ACTIVATION, $group, $user, $activation);
    }

    private function forUser(?User $user, string $field): FilterTarget
    {
        if ($user === null) {
            throw ValidationException::withMessages([$field => 'No user was found.']);
        }
        if ($user->group === null) {
            throw ValidationException::withMessages([$field => 'This user has no group.']);
        }

        return new FilterTarget(FilterTarget::USER, $user->group, $user);
    }

    private function forGroup(?Group $group, string $type, string $field, string $missing = 'No group was found.'): FilterTarget
    {
        if ($group === null) {
            throw ValidationException::withMessages([$field => $missing]);
        }

        return new FilterTarget($type, $group);
    }
}
