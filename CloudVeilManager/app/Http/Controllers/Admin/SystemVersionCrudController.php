<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Request;

/**
 * Class SystemVersionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SystemVersionCrudController extends CrudController
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
        CRUD::setModel(\App\Models\SystemVersion::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/system-version');
        CRUD::setEntityNameStrings('system version', 'system versions');
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
                'label' => 'Platform',
                'type' => 'select',
                'name' => 'platform',
                'attribute' => 'os_name',
            ],
            [
                'label' => 'App Name',
                'type' => 'text',
                'name' => 'app_name',
            ],
            [
                'label' => 'File Name',
                'type' => 'text',
                'name' => 'file_name',
            ],
            [
                'label' => 'Version  Number',
                'type' => 'text',
                'name' => 'version_number',
            ],
            [
                'label' => 'Alpha',
                'type' => 'text',
                'name' => 'alpha',
            ],
            [
                'label' => 'Beta',
                'type' => 'text',
                'name' => 'beta',
            ],
            [
                'label' => 'Stable',
                'type' => 'text',
                'name' => 'stable',
            ],
            [
                'label' => 'Release date',
                'type' => 'date',
                'name' => 'release_date',
            ],
            [
                'label' => 'Active',
                'type' => 'boolean',
                'name' => 'active',
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
            'app_name' => 'required|min:2',
            'version_number' => 'required|min:2',
            'alpha' => 'required|min:2',
            'beta' => 'required|min:2',
            'stable' => 'required|min:2',
            'release_date' => 'required',
            'file_name' => 'required|min:2',
            'file_ext' => 'required|min:2',
            'platform' => 'required',
        ]);

        $this->crud->addFields([
            [
                'label' => 'App Name',
                'type' => 'text',
                'name' => 'app_name',
            ],
            [
                'label' => 'Platform',
                'type' => 'select',
                'name' => 'platform',
                'attribute' => 'os_name',
                'allows_null' => false,
                'wrapper' => ['class' => 'form-group col-md-2'],
            ],
            [
                'label' => 'File Name',
                'type' => 'text',
                'name' => 'file_name',
                'wrapper' => ['class' => 'form-group col-md-8'],
            ],
            [
                'name' => 'file_ext',
                'type' => 'select_from_array',
                'options' => [
                    '.exe' => '.exe',
                    '.dmg' => '.dmg',
                    '.msi' => '.msi',
                    '.run' => '.run',
                ],
                'allows_null' => false,
                'label' => 'File Extension',
                'wrapper' => ['class' => 'form-group col-md-2'],
            ],
            [
                'label' => 'Version  Number',
                'type' => 'text',
                'name' => 'version_number',
            ],
            [
                'label' => 'Alpha Version',
                'type' => 'text',
                'name' => 'alpha',
                'wrapper' => ['class' => 'form-group col-md-4'],
            ],
            [
                'label' => 'Alpha Ed Signature (MacOS Only)',
                'type' => 'text',
                'name' => 'alpha_ed_signature',
                'wrapper' => ['class' => 'form-group col-md-8'],
            ],
            [
                'label' => 'Beta Version',
                'type' => 'text',
                'name' => 'beta',
                'wrapper' => ['class' => 'form-group col-md-4'],
            ],
            [
                'label' => 'Beta Ed Signature (MacOS Only)',
                'type' => 'text',
                'name' => 'beta_ed_signature',
                'wrapper' => ['class' => 'form-group col-md-8'],
            ],
            [
                'label' => 'Stable Version',
                'type' => 'text',
                'name' => 'stable',
                'wrapper' => ['class' => 'form-group col-md-4'],
            ],
            [
                'label' => 'Stable Ed Signature (MacOS Only)',
                'type' => 'text',
                'name' => 'stable_ed_signature',
                'wrapper' => ['class' => 'form-group col-md-8'],
            ],
            [
                'label' => 'Release date',
                'type' => 'datetime',
                'name' => 'release_date',
            ],
            [
                'label' => 'Changes',
                'type' => 'textarea',
                'name' => 'changes',
                'default' => 'N/A'
            ],
            [
                'label' => 'Active',
                'type' => 'boolean',
                'name' => 'active',
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
}
