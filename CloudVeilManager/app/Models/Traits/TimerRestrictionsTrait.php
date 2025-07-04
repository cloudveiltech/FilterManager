<?php

namespace App\Models\Traits;

trait TimerRestrictionsTrait
{
    public static $DEFAULT = [
        "monday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "tuesday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "wednesday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "thursday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "friday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "saturday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "sunday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
    ];

    public function convertTimeToTimeRestrictionFormat($v)
    {
        $timeParts = explode(":", $v);
        if ($timeParts[0] == 23 && $timeParts[1] == 59) {
            return "24.00";
        }
        return $timeParts[0] . "." . $timeParts[1];
    }

    public function isDefaultTimeRestrictions($res) {
        return json_encode($res) == json_encode(TimerRestrictionsTrait::$DEFAULT);
    }



    public function getTimeRestrictionsAttribute()
    {
        $config = $this->config_override["TimeRestrictions"] ?? TimerRestrictionsTrait::$DEFAULT;
        $res = [];
        foreach ($config as $key => $value) {
            $field = [
                "day" => $key,
                "interval"=> [
                    "from" => date('H:i', strtotime(str_replace(".", ":", $value["EnabledThrough"][0]))),
                    "to" => date('H:i', strtotime(str_replace(".", ":", $value["EnabledThrough"][1]))),
                ],
                "enabled" => $value["RestrictionsEnabled"] ?? false
            ];
            if ($field["interval"]["to"] == "00:00" || $field["interval"]["to"] == "24:00") {
                $field["interval"]["to"] = "23:59";
            }
            $res [] = $field;
        }
        return $res;
    }

    public function setTimeRestrictionsAttribute($value)
    {
        $config = $this->config_override;
        $res = [];
        $days = array_keys(TimerRestrictionsTrait::$DEFAULT);
        foreach ($value as $key => $dayData) {
            $day = $days[$key];
            $from = $this->convertTimeToTimeRestrictionFormat($dayData["interval"][0]["from"] ?? "00:00");
            $to = $this->convertTimeToTimeRestrictionFormat($dayData["interval"][0]["to"] ?? "23:59");
            $enabled = $dayData["enabled"];

            $res[$day] = [
                "EnabledThrough" => [
                    $from, $to
                ],
                "RestrictionsEnabled" => (bool)$enabled
            ];
        }

        $isDefaultTimeRestrictions = $this->isDefaultTimeRestrictions($res);
        if($isDefaultTimeRestrictions) {
            unset($config["TimeRestrictions"]);
        } else {
            $config["TimeRestrictions"] = $res;
        }

        $this->config_override = $config;
    }
}
