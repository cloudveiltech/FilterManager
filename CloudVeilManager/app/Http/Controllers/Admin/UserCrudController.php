<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

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
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    protected function setupListOperation()
    {
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


    protected function setupFields($fromEdit = true)
    {
        $validationRules = [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|same:password_verify',
            'password_verify' => 'required',
            'roles' => 'required|exists:roles,id',
            'group' => 'required|exists:groups,id',
            'activations_allowed' => 'required|integer|gt:0',
        ];
        if ($fromEdit) {
            $validationRules["password"] = "same:password_verify";
            unset($validationRules['password_verify']);
        }
        CRUD::setValidation($validationRules);

        $this->crud->addFields([
                [
                    'type' => 'custom_html',
                    'name' => 'activations',
                    'value' => '
                        <a href="javascript:void(0)" onclick="window.open(\''. backpack_url("app-user-activation") .'?q=\' + encodeURIComponent(crud.field(\'email\').value))">Activations</a>
                        ',
                    'tab' => 'Information',
                ],
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
                    'name' => 'is_enabled',
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
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-6'],
                ],
                [
                    'label' => 'Password Confirm',
                    'type' => 'password',
                    'name' => 'password_verify',
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-6'],
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
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-8'],
                ],
                [
                    'label' => 'Enable Relaxed Policy Passcode',
                    'type' => 'switch',
                    'name' => 'enable_relaxed_policy_passcode',
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-2 d-flex pt-3'],
                ],
                [
                    'name' => 'BypassesPermitted',
                    'type' => 'number',
                    'label' => 'Bypasses Permitted',
                    'attributes' => ["min" => "0"],
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-4'],
                ],
                [
                    'name' => 'BypassDuration',
                    'type' => 'number',
                    'label' => 'Bypass Duration, minutes',
                    'attributes' => ["min" => "0"],
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-4'],
                ],
                [
                    'name' => 'DisableBypass',
                    'type' => 'switch',
                    'label' => 'Disable Bypass',
                    'tab' => 'Information',
                    'wrapper' => ['class' => 'form-group col-md-2 d-flex pt-3'],
                ],
                [
                    'type' => 'custom_html',
                    'name' => 'my_custom_html',
                    'tab' => 'Information',
                    'value' => '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            crud.field("DisableBypass").onChange(function(field) {
                                crud.field("BypassesPermitted").disable(field.value == 1);
                                crud.field("BypassDuration").disable(field.value == 1);
                            }).change();
                        });
                    </script>'
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
                            'max-intervals' => 6,
                            'label' => 'Interval',
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

    protected function setupUpdateOperation()
    {
        $this->setupFields(true);
    }

    protected function setupCreateOperation() {
        $this->setupFields(false);
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
        $request = CRUD::getRequest();
        if (!$request->input('password')) {
            $request->request->remove('password_verify');
            $request->request->remove('password');
            CRUD::setRequest($request);
        }

        CRUD::setRequest(CRUD::validateRequest());
        $request = CRUD::getRequest();

        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
            $request->request->remove('password_verify');
        } else {
            $request->request->remove('password');
        }

        CRUD::setRequest($request);
        CRUD::unsetValidation(); // Validation has alr
    }

    public function destroy($id)
    {
        $user = User::where('id', $id)->first();
        if (!is_null($user)) {
            $userTokens = $user->tokens;
            foreach ($userTokens as $token) {
                $token->revoke();
            }

            $user->detachRoles();
            $user->delete();
        }

        return true;
    }
}
