<?php

namespace App\Models\Traits;

use App\Models\Casts\Json;

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
     * config merge (UserController::mergeConfigurations) and the legacy Citadel manager read.
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

    public function setCategoryOverridesAttribute($value)
    {
        $map = is_array($value) ? $value : json_decode((string) $value, true);
        $overrides = [];
        if (is_array($map)) {
            foreach ($map as $categoryId => $override) {
                if (in_array($override, ['Whitelist', 'Blacklist', 'BypassList', 'Ignored'], true)) {
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
