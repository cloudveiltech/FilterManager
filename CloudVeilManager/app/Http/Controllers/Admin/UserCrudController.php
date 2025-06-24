<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    protected function setupListOperation()
    {
        // 'name', 'email', 'password', 'isactive', 'group_id', 'activations_allowed', 'customer_id', 'config_override', 'relaxed_policy_passcode', 'enable_relaxed_policy_passcode'
        $this->crud->setColumns([
            [
                'label' => 'User Name',
                'type' => 'text',
                'name' => 'name',
            ],
            [
                'label' => 'User E-Mail',
                'type' => 'text',
                'name' => 'email',
            ],
            [
                'label' => 'Group Name',
                'type' => 'select',
                'name' => 'group_id',
                'attribute' => 'name',
            ],
            [
                'label' => 'Roles',
                'type' => 'select_multiple',
                'name' => 'roles',
                'entity' => 'roles',
                'attribute' => 'name',
                'model' => 'App\Models\Role',
            ],
            [
                'label' => 'License Used',
                'type' => 'datetime',
                'name' => 'updated_at'
            ],
            [
                'label' => 'Active',
                'type' => 'switch',
                'name' => 'isactive'
            ],
            [
                'label' => 'Created At',
                'type' => 'datetime',
                'name' => 'created_at'
            ],
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $this->crud->addFields([
                [
                    'label' => 'User Full Name',
                    'type' => 'text',
                    'name' => 'name',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'User E-Mail',
                    'type' => 'text',
                    'name' => 'email',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Enabled',
                    'type' => 'switch',
                    'name' => 'isactive',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Customer ID',
                    'type' => 'number',
                    'name' => 'customer_id',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Password',
                    'type' => 'password',
                    'name' => 'password',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Customer ID',
                    'type' => 'number',
                    'name' => 'customer_id',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Activations Allowed',
                    'type' => 'number',
                    'name' => 'activations_allowed',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Group',
                    'type' => 'select2',
                    'entity' => 'group',
                    'model' => 'App\Models\Group',
                    'name' => 'group',
                    'attribute' => 'name',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Roles',
                    'type' => 'select2_multiple',
                    'entity' => 'roles',
                    'model' => 'App\Models\Role',
                    'attribute' => 'display_name',
                    'name' => 'roles',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Relaxed Policy Passcode',
                    'type' => 'password',
                    'name' => 'relaxed_policy_passcode',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Enable Relaxed Policy Passcode',
                    'type' => 'switch',
                    'name' => 'enable_relaxed_policy_passcode',
                    'tab' => 'Information'
                ],
                [
                    'label' => 'Bypass Config',
                    'name' => 'config',
                    'type' => 'repeatable',
                    'tab' => 'Information',
                    'init_rows' => 1,
                    'min_rows' => 1,
                    'max_rows' => 1,
                    'reorder' => false,
                    'subfields' => [
                        [
                            'name' => 'BypassesPermitted',
                            'type' => 'number',
                            'label' => 'Bypasses Permitted',
                            'attributes' => ["min" => "0"],
                            'wrapper' => ['class' => 'form-group col-md-4'],
                        ],
                        [
                            'name' => 'BypassDuration',
                            'type' => 'number',
                            'label' => 'Bypass Duration, minutes',
                            'attributes' => ["min" => "0"],
                            'wrapper' => ['class' => 'form-group col-md-4'],
                        ],
                    ]
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
                    'label' => 'Bypassable Sites',
                    'name' => 'bypassable_sites',
                    'type' => 'table',
                    'tab' => 'Bypassable Sites',
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
                    'entity_singular' => 'Application',
                    'subfields' => [
                        [
                            'name' => 'day',
                            'type' => 'select_from_array',
                            'options' => [
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
                            'name' => 'from',
                            'type' => 'time',
                            'label' => 'From',
                            'wrapper' => ['class' => 'form-group col-md-1'],
                        ],
                        [
                            'name' => 'to',
                            'type' => 'time',
                            'label' => 'To',
                            'wrapper' => ['class' => 'form-group col-md-1'],
                        ],
                        [
                            'name' => 'enabled',
                            'type' => 'switch',
                            'label' => 'Enabled',
                            'wrapper' => ['class' => 'form-group col-md-2 d-flex pt-5'],
                        ],
                    ],
                    'init_rows' => 7,
                    'min_rows' => 7,
                    'max_rows' => 7,
                    'reorder' => false,
                ],
            ]
        );
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->validatePassword();
        return $this->traitStore();
    }

    public function update()
    {
        $this->validatePassword();
        return $this->traitUpdate();
    }

    private function validatePassword()
    {
        CRUD::setRequest(CRUD::validateRequest());

        $request = CRUD::getRequest();

        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        } else {
            $request->request->remove('password');
        }

        CRUD::setRequest($request);
        CRUD::unsetValidation(); // Validation has alr
    }
}
