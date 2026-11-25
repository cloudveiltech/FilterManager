<?php

namespace App\Models\Traits;

use App\Models\Casts\Json;
use App\Models\FilterList;

trait OverridableConfigTrait
{
    protected function casts(): array
    {
        return [
            'config_override' => Json::class,
        ];
    }

    public function getConfigAttribute()
    {
        return [$this->config_override];
    }

    public function setConfigAttribute($value)
    {
        $this->config_override = $value[0] ?? null;
    }

    private function mapConfigArrayToNameValue($arrayKey)
    {
        $array = $this->config_override[$arrayKey] ?? [];
        $result = [];
        foreach ($array as $v) {
            $result[] = ["name" => $v];
        }
        return $result;
    }

    private function assignConfigArray($arrayKey, $value)
    {
        $config = $this->config_override;
        $valueArray = json_decode($value, true);
        $res = [];
        if (is_array($valueArray)) {
            foreach ($valueArray as $value) {
                if(isset($value["name"])) {
                    $res[] = $value["name"];
                }
            }
        }
        if (empty($res)) {
            unset($config[$arrayKey]);
        } else {
            $config[$arrayKey] = $res;
        }
        $this->config_override = $config;
    }

    /**
     * config_override.CategoryOverrides holds per-user/per-activation overrides of the group's
     * rule selection, stored as a list of {categoryId, override} objects — the format the client
     * config merge (UserController::mergeConfigurations) reads.
     * The admin rule-selection field posts and renders a filter_list_id => override map, so
     * convert between the two shapes here.
     */
    public function getCategoryOverridesAttribute()
    {
        $result = [];
        foreach ($this->config_override['CategoryOverrides'] ?? [] as $override) {
            if (isset($override['categoryId'], $override['override'])) {
                $result[$override['categoryId']] = $override['override'];
            }
        }
        return $result;
    }

    /**
     * The category overrides with readable names, for API consumers: a list of
     * {filter_list_id, namespace, category, type, override}. Overrides pointing at a deleted
     * filter list are left out, matching the client config merge, which skips them too.
     */
    public function getCategoryOverrideDetailsAttribute()
    {
        $lists = static::filterListsForOverrideDetails();

        $details = [];
        foreach ($this->category_overrides as $categoryId => $override) {
            $list = $lists->get((int) $categoryId);
            if (is_null($list)) {
                continue;
            }
            $details[] = [
                'filter_list_id' => $list->id,
                'namespace' => $list->namespace,
                'category' => $list->category,
                'type' => $list->type,
                'override' => $override,
            ];
        }
        return $details;
    }

    /**
     * One query per request per model class, shared by every record serialized in it — the manage
     * listings append the details to a whole page of records. Static so once() caches by class
     * rather than per model instance.
     */
    protected static function filterListsForOverrideDetails()
    {
        return once(fn () => FilterList::get(['id', 'namespace', 'category', 'type'])->keyBy('id'));
    }

    /**
     * An override has to make sense for the category's list type, or the client will feed the file
     * to the wrong parser: a Triggers list holds free-text phrases and is only ever loadable as
     * TextTrigger, while a Filters list holds domains/URLs and can be any of the three list types.
     * The admin field only offers the valid options, but this is the boundary that enforces it —
     * anything else is silently dropped rather than written into config_override.
     */
    public static function allowedCategoryOverridesForType($type)
    {
        return $type === 'Triggers'
            ? ['TextTrigger', 'Ignored']
            : ['Whitelist', 'Blacklist', 'BypassList', 'Ignored'];
    }

    public function setCategoryOverridesAttribute($value)
    {
        $map = is_array($value) ? $value : json_decode((string) $value, true);
        $overrides = [];
        if (is_array($map) && !empty($map)) {
            // One query for every referenced category; ids with no matching row are dropped.
            $types = FilterList::whereIn('id', array_keys($map))->pluck('type', 'id')->all();
            foreach ($map as $categoryId => $override) {
                $type = $types[(int) $categoryId] ?? null;
                if (is_null($type)) {
                    continue;
                }
                if (in_array($override, static::allowedCategoryOverridesForType($type), true)) {
                    $overrides[] = ['categoryId' => (int) $categoryId, 'override' => $override];
                }
            }
        }
        $config = $this->config_override;
        if (empty($overrides)) {
            unset($config['CategoryOverrides']);
        } else {
            $config['CategoryOverrides'] = $overrides;
        }
        $this->config_override = $config;
    }

    private function setConfigValue($key, $value) {
        $config = $this->config_override;
        if (empty($value)) {
            unset($config[$key]);
        } else {
            $config[$key] = $value;
        }
        $this->config_override = $config;
    }

    private function getConfigValue($key) {
        $value = $this->config_override[$key] ?? "";
        return $value;
    }
}
