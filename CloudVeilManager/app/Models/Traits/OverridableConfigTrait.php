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
