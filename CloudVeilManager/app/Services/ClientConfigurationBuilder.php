<?php

namespace App\Services;

use App\Client\PlainTextFilteringListType;
use App\Models\AppUserActivation;
use App\Models\FilterList;
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
            /*                 Merge the category overrides                   */
            /* -------------------------------------------------------------- */
            // User and activation CategoryOverrides adjust the group's ConfiguredLists per
            // category: change its ListType, add a category the group doesn't assign, or remove
            // one it does ('Ignored'). An activation override wins over a user override for the
            // same category. Applied before the DisableBypass rewrite below so overridden bypass
            // lists are converted to blacklists like any other.
            $categoryOverrides = array_merge(
                $userConfiguration['CategoryOverrides'] ?? [],
                $activationConfiguration['CategoryOverrides'] ?? []
            );
            if (!empty($categoryOverrides)) {
                $mergedCategoryOverrides = [];
                foreach ($categoryOverrides as $categoryOverride) {
                    if (isset($categoryOverride['categoryId'], $categoryOverride['override'])) {
                        $mergedCategoryOverrides[$categoryOverride['categoryId']] = $categoryOverride;
                    }
                }

                // Resolve every referenced category in one query — this runs on /config/get and
                // /config/check, which both clients poll on a timer, so a per-override lookup here
                // would be N extra queries per poll per device.
                $categories = FilterList::whereIn('id', array_keys($mergedCategoryOverrides))
                    ->get()
                    ->keyBy('id');

                $configuration['ConfiguredLists'] = $configuration['ConfiguredLists'] ?? [];
                foreach ($mergedCategoryOverrides as $categoryOverride) {
                    $category = $categories->get((int) $categoryOverride['categoryId']);
                    if (is_null($category)) {
                        continue;
                    }
                    $path = $category->getRelativeListPath();
                    // look for the category in the ConfiguredLists array
                    $matchIndex = array_search($path, array_column($configuration['ConfiguredLists'], 'RelativeListPath'));
                    if ($matchIndex !== false) {
                        if ($categoryOverride['override'] == 'Ignored') {
                            // remove the category from the ConfiguredLists array
                            unset($configuration['ConfiguredLists'][$matchIndex]);
                            $configuration['ConfiguredLists'] = array_values($configuration['ConfiguredLists']);
                        } else {
                            // merge the category override into the existing category
                            $configuration['ConfiguredLists'][$matchIndex]['ListType'] = $categoryOverride['override'];
                        }
                    } elseif ($categoryOverride['override'] != 'Ignored') {
                        // add the category override to the ConfiguredLists array
                        $configuration['ConfiguredLists'][] = [
                            'ListType' => $categoryOverride['override'],
                            'RelativeListPath' => $path,
                        ];
                    }
                }
            }
            // Internal to the manager — clients only see the resulting ConfiguredLists, and the
            // key would otherwise change the /config/check hash.
            unset($configuration['CategoryOverrides']);

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
