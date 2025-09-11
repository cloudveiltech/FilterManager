<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Class AppUserActivationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AppUserActivationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }


        /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\AppUserActivation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/app-user-activation');
        CRUD::setEntityNameStrings('app user activation', 'app user activations');
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
                'label' => 'User',
                'type' => 'select',
                'name' => 'user_id',
                'attribute' => 'name',
                'key' => 'user_name',
                'priority' => 1
            ],
            [
                'label' => 'E-Mail',
                'type' => 'select',
                'name' => 'user_id',
                'attribute' => 'email',
                'key' => 'user_email',
                'priority' => 2
            ],
            [
                'label' => 'Device ID',
                'type' => 'text',
                'name' => 'device_id',
                'priority' => 1
            ],
            [
                'label' => 'IP Address',
                'type' => 'text',
                'name' => 'ip_address',
                'priority' => 1
            ],
            [
                'label' => 'Bypass',
                'type' => 'text',
                'name' => 'bypass_formatted',
                'priority' => 1
            ],
            [
                'label' => 'Version',
                'type' => 'text',
                'name' => 'app_version',
                'priority' => 1
            ],
            [
                'label' => 'Updated at',
                'type' => 'datetime',
                'name' => 'updated_at',
                'priority' => 1
            ],
            [
                'label' => 'OS',
                'type' => 'text',
                'name' => 'os_formatted',
                'priority' => 1,
                'orderable'  => true,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->orderBy('platform_name', $columnDirection);
                }
            ],
            [
                'label' => 'Friendly Name',
                'type' => 'text',
                'name' => 'friendly_name',
                'priority' => 2
            ],
            [
                'label' => 'Identifier',
                'type' => 'hidden',
                'name' => 'identifier',
                'searchLogic' => 'text'
            ]
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
        ]);

        $this->crud->addFields([
                [
                    'label' => 'User Name',
                    'type' => 'select',
                    'name' => 'user_id',
                    'attribute' => 'name',
                    'tab' => 'Activation',
                    'attributes' => [
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                    ],
                    'wrapper' => ['class' => 'form-group col-md-2'],
                ],
                [
                    'label' => 'Identifier',
                    'type' => 'text',
                    'name' => 'identifier',
                    'tab' => 'Activation',
                    'attributes' => [
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                    ],
                    'wrapper' => ['class' => 'form-group col-md-4'],
                ],
                [
                    'label' => 'Device ID',
                    'type' => 'text',
                    'name' => 'device_id',
                    'tab' => 'Activation',
                    'attributes' => [
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                    ],
                    'wrapper' => ['class' => 'form-group col-md-3'],
                ],
                [
                    'label' => 'IP Address',
                    'type' => 'text',
                    'name' => 'ip_address',
                    'tab' => 'Activation',
                    'attributes' => [
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                    ],
                    'wrapper' => ['class' => 'form-group col-md-2'],
                ],
                [
                    'label' => 'Banned',
                    'type' => 'switch',
                    'name' => 'banned',
                    'tab' => 'Activation',
                ],
                [
                    'name' => 'bypass_quantity',
                    'type' => 'number',
                    'label' => 'Bypasses Permitted',
                    'attributes' => ["min" => "0"],
                    'wrapper' => ['class' => 'form-group col-md-3'],
                    'tab' => 'Activation',
                ],
                [
                    'name' => 'bypass_period',
                    'type' => 'number',
                    'label' => 'Bypass Duration, minutes',
                    'attributes' => ["min" => "0"],
                    'wrapper' => ['class' => 'form-group col-md-3'],
                    'tab' => 'Activation',
                ],
                [
                    'name' => 'bypass_used',
                    'type' => 'number',
                    'label' => 'Bypasses Used',
                    'attributes' => ["min" => "0"],
                    'wrapper' => ['class' => 'form-group col-md-3'],
                    'tab' => 'Activation',
                ],
                [
                    'name' => 'DisableBypass',
                    'type' => 'switch',
                    'label' => 'Disable Bypass',
                    'wrapper' => ['class' => 'form-group col-md-3 d-flex pt-3'],
                    'tab' => 'Activation',
                ],
                [
                    'type' => 'custom_html',
                    'name' => 'my_custom_html',
                    'value' => '<script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        crud.field("DisableBypass").onChange(function(field) {
                                            crud.field("bypass_period").disable(field.value == 1);
                                            crud.field("bypass_quantity").disable(field.value == 1);
                                            crud.field("bypass_used").disable(field.value == 1);
                                        }).change();
                                    });
                                </script>',
                    'tab' => 'Activation',
                ],
                [
                    'label' => 'Group',
                    'type' => 'select2',
                    'entity' => 'group',
                    'model' => 'App\Models\Group',
                    'name' => 'group',
                    'allows_null' => true,
                    'attribute' => 'name',
                    'tab' => 'Activation',
                ],
                [
                    'label' => 'Friendly Name',
                    'type' => 'text',
                    'name' => 'friendly_name',
                    'tab' => 'Activation',
                ],
                [
                    'name' => 'update_channel',
                    'type' => 'select_from_array',
                    'options' => ["Stable" => "Stable", "Alpha" => "Alpha", "Beta" => "Beta"],
                    'label' => 'Update Channel',
                    'tab' => 'Activation',
                ],
                [
                    'label' => 'Blocked Sites',
                    'name' => 'blocked_sites',
                    'type' => 'table',
                    'tab' => 'Blocked Sites',
                    'entity_singular' => 'Site',
                    'columns' => [
                        'name' => 'Domain'
                    ],
                ],
                [
                    'label' => 'Allowed Sites',
                    'name' => 'allowed_sites',
                    'type' => 'table',
                    'tab' => 'Allowed Sites',
                    'entity_singular' => 'Site',
                    'columns' => [
                        'name' => 'Domain'
                    ],
                ],
                [
                    'label' => 'Blocked Triggers',
                    'name' => 'blocked_triggers',
                    'type' => 'table',
                    'tab' => 'Blocked Triggers',
                    'entity_singular' => 'Text Trigger',
                    'columns' => [
                        'name' => 'Text Trigger'
                    ],
                ],
                [
                    'label' => 'Blocked Applications',
                    'name' => 'blocked_applications',
                    'type' => 'table',
                    'tab' => 'Blocked Applications',
                    'entity_singular' => 'Application',
                    'columns' => [
                        'name' => 'App Namme'
                    ],
                ],
                [
                    'label' => 'Time Restrictions',
                    'name' => 'time_restrictions',
                    'type' => 'repeatable',
                    'tab' => 'Time Restrictions',
                    'subfields' => [
                        [
                            'name' => 'day',
                            'type' => 'select_from_array',
                            'options' => [
                                'workdays' => "Workdays",
                                'all' => "All Days",
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            ],
                            'allows_null' => false,
                            'label' => 'Day',
                            'wrapper' => ['class' => 'form-group col-md-2'],
                            'attributes' => [
                                'readonly' => 'readonly',
                                'disabled' => 'disabled',
                            ],
                        ],
                        [
                            'name' => 'intervals',
                            'type' => 'time_range_multiple',
                            'label' => 'Interval',
                            'max-intervals' => 6,
                            'wrapper' => ['class' => 'form-group col-md-7'],
                        ],
                        [
                            'name' => 'enabled',
                            'type' => 'switch',
                            'label' => 'Enabled',
                            'wrapper' => ['class' => 'form-group col-md-3 pt-3'],
                        ],
                    ],
                    'init_rows' => 9,
                    'min_rows' => 9,
                    'max_rows' => 9,
                    'reorder' => false,
                ],
            ]
        );
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

    public function update()
    {
        $this->validatData();
        return $this->traitUpdate();
    }

    private function validatData()
    {
        CRUD::setRequest(CRUD::validateRequest());

        $request = CRUD::getRequest();

        if (!$request->input('group')) {
            $request->request->set('group', -1);
        }
        if (!$request->input('friendly_name')) {
            $request->request->set('friendly_name', "");
        }

        CRUD::setRequest($request);
        CRUD::unsetValidation();
    }
}
