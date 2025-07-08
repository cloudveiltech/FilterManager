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
            $intervals = [];
            $enabledThrough = $value["EnabledThrough"];
            for($i=0; $i<count($enabledThrough); $i+=2) {
                $interval = [
                    "from" => date('H:i', strtotime(str_replace(".", ":", $enabledThrough[$i]))),
                    "to" => date('H:i', strtotime(str_replace(".", ":", $enabledThrough[$i+1]))),
                ];

                if ($interval["to"] == "00:00" || $interval["to"] == "24:00") {
                    $interval["to"] = "23:59";
                }

                $intervals[] = $interval;
            }
            $field = [
                "day" => $key,
                "intervals"=> $intervals,
                "enabled" => $value["RestrictionsEnabled"] ?? false
            ];
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
            $intervals = $dayData["intervals"];
            $enabledThrough = [];
            for($i=0; $i<count($intervals); $i++) {
                $from = $this->convertTimeToTimeRestrictionFormat($intervals[$i]["from"] ?? "00:00");
                $to = $this->convertTimeToTimeRestrictionFormat($intervals[$i]["to"] ?? "23:59");
                $enabledThrough[] = $from;
                $enabledThrough[] = $to;
            }

            $enabled = $dayData["enabled"];

            $res[$day] = [
                "EnabledThrough" => $enabledThrough,
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
