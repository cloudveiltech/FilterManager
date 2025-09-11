<?php

namespace App\Http\Controllers\Admin;

use App\Models\FilterList;
use App\Models\Group;
use App\Models\SystemPlatform;
use App\Models\UserGroupToAppGroup;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Class GroupCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class GroupCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation {
        destroy as traitDelete;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Group::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/group');
        CRUD::setEntityNameStrings('group', 'groups');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->setColumns([
            [
                'label' => 'Name',
                'type' => 'text',
                'name' => 'name',
            ],
            [
                'label' => 'Primary DNS',
                'type' => 'text',
                'name' => 'primary_dns',
            ],
            [
                'label' => 'Secondary DNS',
                'type' => 'text',
                'name' => 'secondary_dns',
            ],
            [
                'label' => 'Bypass',
                'type' => 'text',
                'name' => 'bypass_formatted',
            ],
            [
                'label' => 'Date Registered',
                'type' => 'datetime',
                'name' => 'name',
            ],
            [
                'label' => 'Notes',
                'type' => 'text',
                'name' => 'name',
                'priority' => 2,
            ],
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|min:2',
        ]);

        CRUD::setOperationSetting('strippedRequest', function ($request) {
            $input = $request->only(CRUD::getAllFieldNames());

            if (is_array($input["assignedBlockedApplicationRules"])) {
                foreach ($input["assignedBlockedApplicationRules"] as &$assignedBlockedApplicationRule) {
                    $assignedBlockedApplicationRule = [
                        "assignedBlockedApplicationRules" => $assignedBlockedApplicationRule,
                        "filter_type" => UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS
                    ];
                }
            }

            if (is_array($input["assignedApplicationRules"])) {
                $type = $input["assigned_application_rules_type"];
                foreach ($input["assignedApplicationRules"] as &$assignedApplicationRule) {
                    $assignedApplicationRule = [
                        "assignedApplicationRules" => $assignedApplicationRule,
                        "filter_type" => $type
                    ];
                }
            }
            return $input;
        });

        $this->crud->addFields([
            [
                'label' => 'Name',
                'type' => 'text',
                'name' => 'name',
                'tab' => 'Settings'
            ],
            [
                'label' => 'Enabled',
                'type' => 'switch',
                'name' => 'isactive',
                'tab' => 'Settings'
            ],
            [
                'label' => 'Config',
                'name' => 'group_config',
                'type' => 'repeatable',
                'tab' => 'Settings',
                'subfields' => [
                    [
                        'name' => 'UpdateFrequency',
                        'type' => 'number',
                        'label' => 'Update Frequency',
                        'wrapper' => ['class' => 'form-group col-md-8'],
                    ],
                    [
                        'name' => 'PrimaryDns',
                        'type' => 'text',
                        'label' => 'Primary Dns',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],

                    [
                        'name' => 'PrimaryDnsV6',
                        'type' => 'text',
                        'label' => 'Primary Dns V6',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name' => 'SecondaryDns',
                        'type' => 'text',
                        'label' => 'Secondary Dns',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name' => 'SecondaryDnsV6',
                        'type' => 'text',
                        'label' => 'Secondary Dns V6',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name' => 'UpdateChannel',
                        'type' => 'select_from_array',
                        'options' => ["Stable" => "Stable", "Alpha" => "Alpha", "Beta" => "Beta"],
                        'label' => 'Update Channel',
                    ],
                    [
                        'name' => 'BypassesPermitted',
                        'type' => 'number',
                        'label' => 'Bypasses Permitted',
                        'wrapper' => ['class' => 'form-group col-md-4'],
                    ],
                    [
                        'name' => 'BypassDuration',
                        'type' => 'number',
                        'label' => 'Bypass Duration',
                        'wrapper' => ['class' => 'form-group col-md-4'],
                    ],
                    [
                        'name' => 'DisableBypass',
                        'type' => 'switch',
                        'label' => 'Disable Bypass',
                        'wrapper' => ['class' => 'form-group col-md-2 d-flex pt-3'],
                    ],
                    [
                        'type' => 'custom_html',
                        'name' => 'my_custom_html',
                        'value' => '<script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    crud.field("group_config[0][DisableBypass]").onChange(function(field) {
                                        crud.field("group_config[0][BypassesPermitted]").disable(field.value == 1);
                                        crud.field("group_config[0][BypassDuration]").disable(field.value == 1);
                                    }).change();
                                });
                            </script>'
                    ]
                ],

                'init_rows' => 1,
                'min_rows' => 1,
                'max_rows' => 1,
                'reorder' => false,
            ],
            [
                'label' => 'Notes',
                'type' => 'textarea',
                'name' => 'notes',
                'tab' => 'Settings',
            ],
            [
                'label' => 'Whitelist Rules',
                'type' => 'relationship',
                'name' => 'assignedWhitelistFilters',
                'entity' => 'assignedWhitelistFilters',
                'attribute' => 'label',
                'model' => 'App\Models\FilterList',
                'pivot' => true,
                'tab' => 'Rule Selection',
                'options' => function ($query) {
                    return $query->orderBy('category', 'ASC')->get();
                }
            ],
            [
                'label' => 'Blacklist Rules',
                'type' => 'relationship',
                'name' => 'assignedBlacklistFilters',
                'entity' => 'assignedBlacklistFilters',
                'attribute' => 'label',
                'model' => 'App\Models\FilterList',
                'pivot' => true,
                'tab' => 'Rule Selection',
                'options' => function ($query) {
                    return $query->orderBy('category', 'ASC')->get();
                }
            ],
            [
                'label' => 'Bypass Rules',
                'type' => 'relationship',
                'name' => 'assignedBypassFilters',
                'entity' => 'assignedBypassFilters',
                'attribute' => 'label',
                'model' => 'App\Models\FilterList',
                'pivot' => true,
                'tab' => 'Rule Selection',
                'options' => function ($query) {
                    return $query->orderBy('category', 'ASC')->get();
                },
            ],
            [
                'type' => 'custom_html',
                'name' => 'my_custom_html',
                'value' => '<script>
                            function hideConnectedOptions(elName, otherName, label) {
                                $("select[name=\'" + otherName + "\'] option").each(function(i, el) {
                                    let val = $(el).val();
                                    let selected = $(el).is(":selected");
                                    $("select[name=\'" + elName + "\']").find("option[value=\'" + val + "\']").each(function(i, optionEl) {
                                        let text = $(optionEl).text();
                                        if(selected) {
                                            if(text.indexOf(" — #") === -1) {
                                                text = text + " — #" + label;
                                            }
                                        } else {
                                            if(text.indexOf(" — #" + label) !== -1) {
                                                text = text.replace(" — #" + label, "")
                                            }
                                        }
                                        $(optionEl).text(text).prop("disabled", text.indexOf(" — #") !== -1);
                                    });
                                });

                                let recreateEl = $("select[name=\'" + elName + "\']");
                                if (recreateEl.hasClass("select2-hidden-accessible")) {
                                    recreateEl.select2("destroy");
                                    bpFieldInitRelationshipSelectElement(recreateEl);
                                }
                            }
                                document.addEventListener("DOMContentLoaded", function() {
                                    crud.field("assignedWhitelistFilters").onChange(function(field) {
                                        hideConnectedOptions("assignedBypassFilters[]", "assignedWhitelistFilters[]", "whitelist");
                                        hideConnectedOptions("assignedBlacklistFilters[]", "assignedWhitelistFilters[]", "whitelist");
                                    }).change();
                                    crud.field("assignedBlacklistFilters").onChange(function(field) {
                                        hideConnectedOptions("assignedWhitelistFilters[]", "assignedBlacklistFilters[]", "blacklist");
                                        hideConnectedOptions("assignedBypassFilters[]", "assignedBlacklistFilters[]", "blacklist");
                                    }).change();
                                    crud.field("assignedBypassFilters").onChange(function(field) {
                                        hideConnectedOptions("assignedWhitelistFilters[]", "assignedBypassFilters[]", "bypasslist");
                                        hideConnectedOptions("assignedBlacklistFilters[]", "assignedBypassFilters[]", "bypasslist");
                                    }).change();
                                });
                                </script>',
                'tab' => 'Rule Selection',
            ],
            [
                'label' => 'Application Groups Type',
                'name' => 'assigned_application_rules_type',
                'type' => 'radio',
                'options' => [
                    UserGroupToAppGroup::FILTER_TYPE_WHITELIST => "Whitelist",
                    UserGroupToAppGroup::FILTER_TYPE_BLACKLIST => "Blacklist"
                ],
                'inline' => true,
                'tab' => 'Application Selection',
            ],
            [
                'label' => 'Application Groups',
                'type' => 'relationship',
                'name' => 'assignedApplicationRules',
                'entity' => 'assignedApplicationRules',
                'attribute' => 'group_name',
                'model' => 'App\Models\AppGroup',
                'tab' => 'Application Selection',
                'options' => function ($query) {
                    return $query->orderBy('group_name', 'ASC')->get();
                }
            ],
            [
                'label' => 'Application Blocking',
                'type' => 'relationship',
                'pivot' => true,
                'name' => 'assignedBlockedApplicationRules',
                'entity' => 'assignedBlockedApplicationRules',
                'attribute' => 'group_name',
                'model' => 'App\Models\AppGroup',
                'tab' => 'Application Selection',
                'options' => function ($query) {
                    return $query->orderBy('group_name', 'ASC')->get();
                }
            ],
        ]);
    }


    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->patchRules();
        $result = $this->traitStore();
        $model = $this->data["entry"] ?? null;
        if ($model) {
            $model->rebuildGroupData();
        }
        return $result;
    }

    public function update()
    {
        $this->patchRules();
        $result = $this->traitUpdate();
        $model = $this->data["entry"] ?? null;
        if ($model) {
            $model->rebuildGroupData();
        }
        return $result;
    }

    private function patchRules()
    {
        $this->patchRule("assignedBlacklistFilters", 1, 0, 0);
        $this->patchRule("assignedWhitelistFilters", 0, 1, 0);
        $this->patchRule("assignedBypassFilters", 0, 0, 1);
    }

    private function patchRule($key, $asBlacklist, $asWhitelist, $asBypass)
    {
        $request = CRUD::getRequest();
        $listFilters = $request->input($key);
        if (!empty($listFilters)) {
            $newData = [];
            foreach ($listFilters as $listFilterId) {
                $newData[] = [
                    $key => $listFilterId,
                    "as_blacklist" => $asBlacklist,
                    "as_whitelist" => $asWhitelist,
                    "as_bypass" => $asBypass
                ];
            }

            $request->request->set($key, $newData);
            CRUD::setRequest($request);
        }
    }

    public function destroy($id)
    {
        $group = Group::find($id);
        if ($group) {
            $group->destroyGroupData();
        }
        return $this->traitDelete($id);
    }
}
