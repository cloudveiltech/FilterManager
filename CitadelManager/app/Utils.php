<?php

namespace App;

class Utils {
	public static function purgeNulls($arr) {
		$ret = [];

		foreach($arr as $value) {
			if($value != null) {
				$ret[] = $value;
			}
		}

		return $ret;
	}

	public static function purgeNullsFromSelfModerationArrays($configOverride) {
		$configOverride['SelfModeration'] = Utils::purgeNulls($configOverride['SelfModeration']);
		$configOverride['CustomWhitelist'] = Utils::purgeNulls($configOverride['CustomWhitelist']);
		$configOverride['CustomTriggerBlacklist'] = Utils::purgeNulls($configOverride['CustomTriggerBlacklist']);

		return $configOverride;
	}
}