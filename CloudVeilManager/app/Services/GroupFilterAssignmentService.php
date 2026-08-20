<?php

namespace App\Services;

use App\Models\FilterList;
use App\Models\Group;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

class GroupFilterAssignmentService
{
    public const ACTIVE_STATUSES = ['blacklist', 'whitelist', 'bypass'];

    public function statusesFromRequest(Request $request, string $inputName = 'rule_status_json'): array
    {
        $decoded = json_decode((string) $request->input($inputName, ''), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function syncGroupAssignments(Group $group, array $statuses): array
    {
        $changedFilterIds = $this->syncRelation(
            $group->assignedFilters(),
            $this->desiredPivotStates($statuses)
        );

        return $changedFilterIds === [] ? [] : [(int) $group->getKey()];
    }

    public function syncFilterListAssignments(FilterList $filterList, array $statuses): array
    {
        return $this->syncRelation(
            $filterList->groups(),
            $this->desiredPivotStates($statuses)
        );
    }

    public function updateFilterListGroupAssignments(FilterList $filterList, array $statuses): array
    {
        $relation = $filterList->groups();
        $current = $this->currentPivotStates($relation);
        $desired = $current;

        foreach ($statuses as $id => $status) {
            $id = (int) $id;

            if ($id < 1) {
                continue;
            }

            $attributes = $this->pivotAttributesForStatus($status);

            if ($attributes === null) {
                unset($desired[$id]);
            } else {
                $desired[$id] = $attributes;
            }
        }

        return $this->syncRelation($relation, $desired, $current);
    }

    public function statusFromPivot($pivot): string
    {
        if ((int) data_get($pivot, 'as_blacklist') === 1) {
            return 'blacklist';
        }

        if ((int) data_get($pivot, 'as_whitelist') === 1) {
            return 'whitelist';
        }

        if ((int) data_get($pivot, 'as_bypass') === 1) {
            return 'bypass';
        }

        return 'ignored';
    }

    public function pivotAttributesForStatus($status): ?array
    {
        return match ($status) {
            'blacklist' => [
                'as_blacklist' => 1,
                'as_whitelist' => 0,
                'as_bypass' => 0,
            ],
            'whitelist' => [
                'as_blacklist' => 0,
                'as_whitelist' => 1,
                'as_bypass' => 0,
            ],
            'bypass' => [
                'as_blacklist' => 0,
                'as_whitelist' => 0,
                'as_bypass' => 1,
            ],
            default => null,
        };
    }

    private function syncRelation(BelongsToMany $relation, array $desired, ?array $current = null): array
    {
        $current ??= $this->currentPivotStates($relation);

        $candidateIds = array_unique(array_merge(array_keys($current), array_keys($desired)));
        $changedIds = array_values(array_filter(
            $candidateIds,
            fn ($id) => ($current[$id] ?? null) !== ($desired[$id] ?? null)
        ));

        if ($changedIds === []) {
            return [];
        }

        $relation->sync($desired);

        return array_map('intval', $changedIds);
    }

    private function currentPivotStates(BelongsToMany $relation): array
    {
        $current = [];

        foreach ($relation->get() as $assigned) {
            $current[(int) $assigned->getKey()] = [
                'as_blacklist' => (int) $assigned->pivot->as_blacklist,
                'as_whitelist' => (int) $assigned->pivot->as_whitelist,
                'as_bypass' => (int) $assigned->pivot->as_bypass,
            ];
        }

        return $current;
    }

    private function desiredPivotStates(array $statuses): array
    {
        $desired = [];

        foreach ($statuses as $id => $status) {
            $attributes = $this->pivotAttributesForStatus($status);
            $id = (int) $id;

            if ($attributes === null || $id < 1) {
                continue;
            }

            $desired[$id] = $attributes;
        }

        return $desired;
    }
}
