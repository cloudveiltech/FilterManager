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

/**
 * Class GroupCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class GroupCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation{
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

            if(is_array($input["assignedBlockedApplicationRules"])) {
                foreach ($input["assignedBlockedApplicationRules"] as &$assignedBlockedApplicationRule) {
                    $assignedBlockedApplicationRule = [
                        "assignedBlockedApplicationRules" => $assignedBlockedApplicationRule,
                        "filter_type" => UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS
                    ];
                }
            }

            if(is_array($input["assignedApplicationRules"])) {
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
                        'wrapper' => ['class' => 'form-group col-md-8'],
                    ],
                    [
                        'name' => 'BypassesPermitted',
                        'type' => 'number',
                        'label' => 'Bypasses Permitted',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name' => 'BypassDuration',
                        'type' => 'number',
                        'label' => 'Bypass Duration',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
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
                'type' => 'select2_from_ajax_multiple',
                'name' => 'assignedWhitelistFilters',
                'entity' => 'assignedWhitelistFilters',
                'attribute' => 'label',
                'model' => 'App\Models\FilterList',
                'pivot' => true,
                'data_source' => url("admin/api/filter_list?type=whitelist"),
                'method' => 'post',
                'delay' => 500,
                'include_all_form_fields' => true,
                'minimum_input_length' => 3,
                'tab' => 'Rule Selection',
            ],
            [
                'label' => 'Blacklist Rules',
                'type' => 'select2_from_ajax_multiple',
                'name' => 'assignedBlacklistFilters',
                'entity' => 'assignedBlacklistFilters',
                'attribute' => 'label',
                'model' => 'App\Models\FilterList',
                'pivot' => true,
                'data_source' => url("admin/api/filter_list?type=blacklist"),
                'method' => 'post',
                'delay' => 500,
                'include_all_form_fields' => true,
                'minimum_input_length' => 3,
                'tab' => 'Rule Selection',
            ],
            [
                'label' => 'Bypass Rules',
                'type' => 'select2_from_ajax_multiple',
                'name' => 'assignedBypassFilters',
                'entity' => 'assignedBypassFilters',
                'attribute' => 'label',
                'model' => 'App\Models\FilterList',
                'pivot' => true,
                'data_source' => url("admin/api/filter_list?type=bypass"),
                'method' => 'post',
                'delay' => 500,
                'include_all_form_fields' => true,
                'minimum_input_length' => 3,
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

    function loadFilterLists(Request $request)
    {
        $type = $request->input("type");
        $query = $request->input("q");
        $formData = $request->input("form");
        $groupId = null;

        $formKeys = [
            "whitelist" => "assignedWhitelistFilters[]",
            "blacklist" => "assignedBlacklistFilters[]",
            "bypass" => "assignedBypassFilters[]",
        ];

        $occupiedFilterIds = [];
        foreach ($formData as $inputData) {
            $name = $inputData["name"];
            $value = $inputData["value"];
            if ($name == "id") {
                $groupId = $value;
            }

            if (in_array($name, $formKeys)) {
                $occupiedFilterIds[] = (int)$value;
            }
        }

        $group = Group::findOrFail($groupId);

        return FilterList::where("namespace", "like", "%" . $query . "%")
            ->orWhere("category", "like", "%" . $query . "%")
            ->whereNotIn("id", $occupiedFilterIds)
            ->get();
    }

    public function store()
    {
        $result = $this->traitStore();
        $model = $this->data["entry"] ?? null;
        if($model) {
            $model->rebuildGroupData();
        }
        return $result;
    }

    public function update()
    {
        $result = $this->traitUpdate();
        $model = $this->data["entry"] ?? null;
        if($model) {
            $model->rebuildGroupData();
        }
        return $result;
    }

    public function destroy($id)
    {
        $group = Group::find($id);
        if($group) {
            $group->destroyGroupData();
        }
        return $this->traitDelete($id);
    }
}
