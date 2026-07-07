<?php

namespace App\Models\Helpers;

class Utils {
	public static function purgeNulls($arr) {
		$ret = [];

		foreach($arr as $key=>$value) {
			if($value != null) {
				$ret[$key] = $value;
			}
		}

		return $ret;
	}

	private static function purgeNullsFromArrayWithKey($arr, $key) {
		if(isset($arr[$key])) {
			return Utils::purgeNulls($arr[$key]);
		}

		return null;
	}

	public static function purgeNullsFromSelfModerationArrays($configOverride) {
		$configOverride['SelfModeration'] = Utils::purgeNullsFromArrayWithKey($configOverride, 'SelfModeration');
		$configOverride['CustomWhitelist'] = Utils::purgeNullsFromArrayWithKey($configOverride, 'CustomWhitelist');
		$configOverride['CustomTriggerBlacklist'] = Utils::purgeNullsFromArrayWithKey($configOverride, 'CustomTriggerBlacklist');
        $configOverride['CustomBypasslist'] = Utils::purgeNullsFromArrayWithKey($configOverride, 'CustomBypasslist');
        $configOverride['CustomBlockedApps'] = Utils::purgeNullsFromArrayWithKey($configOverride, 'CustomBlockedApps');

		return $configOverride;
	}

	public static function purgeNullsFromJSONSelfModeration($configOverrideString) {
		if($configOverrideString) {
			$configOverride = json_decode($configOverrideString, true);

			if($configOverride) {
				$configOverride = Utils::purgeNullsFromSelfModerationArrays($configOverride);
				return json_encode($configOverride);
			}
		}

		return $configOverrideString;
	}
}
