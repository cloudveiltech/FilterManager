<?php

namespace App\Models\Traits;

trait TimerRestrictionsTrait
{
    public static $WORKDAYS = ["monday", "tuesday", "wednesday", "thursday", "friday"];
    public static $ALL_DAYS = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
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
    public static $TEMPLATES = [
        "workdays" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "all" => [
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

    public function compareArrays($res1, $res2) {
        return json_encode($res1) == json_encode($res2);
    }

    public function getTimeRestrictionsAttribute()
    {
        $config = $this->config_override["TimeRestrictions"] ?? self::$DEFAULT;
        $templateConfig = $this->config_override["TimeRestrictionsTemplates"] ?? self::$TEMPLATES;
        $config = $templateConfig + $config;

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
            $res[$key] = $field;
        }
        return array_values($res);
    }

    public function setTimeRestrictionsAttribute($value)
    {
        $config = $this->config_override;
        $res = [];
        $days = array_keys(self::$TEMPLATES + self::$DEFAULT);
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

        $templates = [
            "workdays" => $res["workdays"],
            "all" => $res["all"],
        ];
        unset($res["workdays"]);
        unset($res["all"]);

        $isDefaultTimeRestrictions = $this->compareArrays($res, self::$DEFAULT);
        if($isDefaultTimeRestrictions) {
            unset($config["TimeRestrictions"]);
        } else {
            $config["TimeRestrictions"] = $res;
        }

        $isDefaultTemplates = $this->compareArrays($templates, self::$TEMPLATES);
        if($isDefaultTemplates) {
            unset($config["TimeRestrictionsTemplates"]);
        } else {
            $config["TimeRestrictionsTemplates"] = $templates;
        }
        $this->config_override = $config;
    }

    public static  function applyTemplates($timeRestrictions, $templates): array {
        $timeRestrictions = self::applyTemplate($timeRestrictions, $templates["workdays"], self::$WORKDAYS);
        $timeRestrictions = self::applyTemplate($timeRestrictions, $templates["all"], self::$ALL_DAYS);
        return $timeRestrictions;
    }

    public static function applyTemplate($timeRestrictions, $template, $days): array {
        if($template["RestrictionsEnabled"]) {
            foreach($days as $day) {
                if(!$timeRestrictions[$day]["RestrictionsEnabled"]) {
                    $timeRestrictions[$day]["EnabledThrough"] = $template["EnabledThrough"];
                    $timeRestrictions[$day]["RestrictionsEnabled"] = true;
                }
            }
        }
        return $timeRestrictions;
    }
}
