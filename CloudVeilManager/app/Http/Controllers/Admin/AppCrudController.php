<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AppCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AppCrudController extends CrudController
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
        CRUD::setModel(\App\Models\App::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/app');
        CRUD::setEntityNameStrings('app', 'apps');
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
                'label' => 'Application Name',
                'type' => 'text',
                'name' => 'name',
                'priority' => 1,
            ],
            [
                'label' => 'Notes',
                'type' => 'text',
                'name' => 'notes',
                'priority' => 1,
            ],
            [
                'label' => 'Linked Groups',
                'type' => 'select_multiple',
                'name' => 'group',
                'entity' => 'group',
                'attribute' => 'group_name',
                'model'     => 'App\Models\AppGroup',
                'priority' => 2,
            ],
            [
                'label' => 'Updated at',
                'type' => 'datetime',
                'name' => 'updated_at',
                'priority' => 1,
            ],
            [
                'label' => 'Platform',
                'type' => 'text',
                'name' => 'platform_name',
                'priority' => 1,
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
