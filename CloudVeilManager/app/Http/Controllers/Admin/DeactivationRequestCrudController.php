<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DeactivationRequestCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DeactivationRequestCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\EditableColumns\Http\Controllers\Operations\MinorUpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\DeactivationRequest::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/deactivation-request');
        CRUD::setEntityNameStrings('deactivation request', 'deactivation requests');
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
                'label' => 'User Full Name',
                'type' => 'select',
                'name' => 'user_id',
                'attribute' => 'name',
                'key' => "user_id_name"
            ],
            [
                'label' => 'User Email',
                'type' => 'select',
                'name' => 'user_id',
                'attribute' => 'email',
                'key' => "user_id_email"
            ],
            [
                'label' => 'Activation ID',
                'type' => 'text',
                'name' => 'identifier',
            ],
            [
                'label' => 'Device ID',
                'type' => 'text',
                'name' => 'device_id',
            ],
            [
                'label' => 'Granted',
                'type' => 'editable_switch',
                'name' => 'granted',
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
        $this->crud->addFields([
            [
                'label' => 'User Full Name',
                'type' => 'select',
                'name' => 'user_id',
                'attribute' => 'name',
                'key' => "user_id_name",
                'attributes' => [
                    'disabled' => 'disabled',
                ],
            ],
            [
                'label' => 'User Email',
                'type' => 'select',
                'name' => 'user_id',
                'attribute' => 'email',
                'key' => "user_id_email",
                'attributes' => [
                    'disabled' => 'disabled',
                ],
            ],
            [
                'label' => 'Activation ID',
                'type' => 'text',
                'name' => 'identifier',
                'attributes' => [
                    'disabled' => 'disabled',
                ],
            ],
            [
                'label' => 'Device ID',
                'type' => 'text',
                'name' => 'device_id',
                'attributes' => [
                    'disabled' => 'disabled',
                ],
            ],
            [
                'label' => 'Granted',
                'type' => 'switch',
                'name' => 'granted',
            ]
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
}
