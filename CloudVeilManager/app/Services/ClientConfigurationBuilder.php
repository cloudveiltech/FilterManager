<?php

namespace App\Services;

use App\Client\PlainTextFilteringListType;
use App\Models\AppUserActivation;
use App\Models\Helpers\Utils;

/**
 * Builds the configuration a device receives: the group's compiled config with the
 * user's and the activation's overrides and custom lists merged in.
 */
class ClientConfigurationBuilder
{
    /**
     * @param  \App\Models\Group|null  $userGroup
     * @param  \App\Models\User  $thisUser
     * @param  \App\Models\AppUserActivation  $activation
     */
    public function build($userGroup, $thisUser, $activation): ?array
    {
        if (!is_null($userGroup)) {
            if ($userGroup->config_cache == null || strlen($userGroup->config_cache) == 0) {
                $userGroup->rebuildGroupData();
            }

            $groupConfiguration = json_decode($userGroup->config_cache, true) ?? [];
            $userConfiguration = $thisUser->config_override ?? [];
            $activationConfiguration = $activation->config_override ?? [];

            $activationConfiguration = Utils::purgeNulls($activationConfiguration);

            $configuration = array_merge($groupConfiguration, $userConfiguration, $activationConfiguration);

            /* -------------------------------------------------------------- */
            /*                 Merge the arrays for these keys                */
            /* -------------------------------------------------------------- */
            $properties = [
                'SelfModeration',
                'CustomWhitelist',
                'CustomBypasslist',
                'CustomTriggerBlacklist',
                'CustomBlockedApps'
            ];

            foreach ($properties as $property) {
                $configuration[$property] = [];
                // merge the arrays, remove duplicates and reset the keys
                $userConfig = $userConfiguration[$property] ?? [];
                $activationConfig = $activationConfiguration[$property] ?? [];
                if(is_array($userConfig) && is_array($activationConfig)) {
                    $configuration[$property] = array_values(array_unique(array_merge($userConfig, $activationConfig)));
                }
            }

            $configuration = Utils::purgeNullsFromSelfModerationArrays($configuration);

            $configuration['CustomTriggerBlacklist'] = array_map("strtolower", $configuration['CustomTriggerBlacklist']);

            $configuration['DebugEnabled'] = 0;
            if ($activation->debug_enabled) {
                $configuration['DebugEnabled'] = $activation->debug_enabled;
            } else if (isset($userConfiguration["DebugEnabled"])) {
                $configuration['DebugEnabled'] = $userConfiguration["DebugEnabled"];
            } else if (isset($groupConfiguration["DebugEnabled"])) {
                $configuration['DebugEnabled'] = $groupConfiguration["DebugEnabled"];
            }

            if ($activation->bypass_quantity) {
                $configuration['BypassesPermitted'] = $activation->bypass_quantity;
            }

            if ($activation->bypass_period) {
                $configuration['BypassDuration'] = $activation->bypass_period;
            }

            if ($thisUser->enable_relaxed_policy_passcode) {
                $configuration['EnableRelaxedPolicyPasscode'] = $thisUser->enable_relaxed_policy_passcode;
            }

            $configuration["FriendlyName"] = $activation->friendly_name;

            $bypassDisabled = $configuration["DisableBypass"] ?? false;
            if ($bypassDisabled || !isset($configuration['BypassesPermitted'])) {
                $configuration['BypassesPermitted'] = 0;
            }
            if ($bypassDisabled || !isset($configuration['BypassDuration'])) {
                $configuration['BypassDuration'] = 0;
            }

            if($bypassDisabled) {
                $configuration["SelfModeration"] = array_merge($configuration["SelfModeration"] ?? [], $configuration["CustomBypasslist"] ?? []);
                foreach ($configuration["ConfiguredLists"] as &$list) {
                    if($list["ListType"] == PlainTextFilteringListType::BypassList) {
                        $list["ListType"] = PlainTextFilteringListType::Blacklist;
                    }
                }
            }

            if (isset($configuration["TimeRestrictionsTemplates"])) {
                $configuration["TimeRestrictions"] = AppUserActivation::applyTemplates($configuration["TimeRestrictions"] ?? [], $configuration["TimeRestrictionsTemplates"] ?? []);
                unset($configuration["TimeRestrictionsTemplates"]);
            }
            return $configuration;
        } else {
            return null;
        }
    }
}
