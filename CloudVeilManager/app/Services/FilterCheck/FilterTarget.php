<?php

namespace App\Services\FilterCheck;

use App\Models\AppUserActivation;
use App\Models\Group;
use App\Models\User;

/**
 * What a filter check runs against: a device, a user, a group, or the default
 * group, with the group whose lists apply.
 */
final class FilterTarget
{
    public const ACTIVATION = 'activation';

    public const USER = 'user';

    public const GROUP = 'group';

    public const DEFAULT_GROUP = 'default_group';

    public function __construct(
        public readonly string $type,
        public readonly Group $group,
        public readonly ?User $user = null,
        public readonly ?AppUserActivation $activation = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'activation_id' => $this->activation?->id,
            'user_id' => $this->user?->id,
            'group' => [
                'id' => $this->group->id,
                'name' => $this->group->name,
            ],
        ];
    }
}
