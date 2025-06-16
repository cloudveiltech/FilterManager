<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AppUserActivationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AppUserActivationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

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
                'type'      => 'select',
                'name'      => 'user_id',
                'attribute' => 'name',
            ],
            [
                'label' => 'Device ID',
                'type' => 'text',
                'name' => 'device_id',
            ],
            [
                'label' => 'IP Address',
                'type' => 'text',
                'name' => 'ip_address',
            ],
            [
                'label' => 'Bypass',
                'type' => 'text',
                'name' => 'bypass_formatted',
            ],
            [
                'label' => 'Version',
                'type' => 'text',
                'name' => 'app_version',
            ],
            [
                'label' => 'Updated at',
                'type' => 'datetime',
                'name' => 'updated_at',
            ],
            [
                'label' => 'OS',
                'type' => 'text',
                'name' => 'os_formatted',
            ],
            [
                'label' => 'Friendly Name',
                'type' => 'text',
                'name' => 'friendly_name',
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
