<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
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
                'type'      => 'select',
                'name'      => 'group_id',
                'attribute' => 'name',
            ],
            [
                'label' => 'Roles',
                'type' => 'select_multiple',
                'name' => 'roles',
                'entity' => 'roles',
                'attribute' => 'name',
                'model'     => 'App\Models\Role',
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

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            // 'name' => 'required|min:2',
        ]);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
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
}
