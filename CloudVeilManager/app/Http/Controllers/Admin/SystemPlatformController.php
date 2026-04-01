<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemPlatform;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

/**
 * Class SystemVersionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SystemPlatformController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SystemPlatform::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/system-platform');
        CRUD::setEntityNameStrings('system platform', 'system platforms');
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
                'type' => 'text',
                'name' => 'platform',
                'attribute' => 'os_name',
            ],
            [
                'label' => 'Os Name',
                'type' => 'text',
                'name' => 'os_name',
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
            'platform' => 'required|min:2',
            'os_name' => 'required|min:2',
        ]);

        $this->crud->addFields([
            [
                'label' => 'Platform',
                'type' => 'select_from_array',
                'options' => array_combine(SystemPlatform::PLATFORM_SUPPORTED, SystemPlatform::PLATFORM_SUPPORTED),
                'name' => 'platform',
                'attribute' => 'os_name',
            ],
            [
                'label' => 'Os Name',
                'type' => 'text',
                'name' => 'os_name',
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

    public function update()
    {
        $result = $this->traitUpdate();
        $model = $this->data["entry"] ?? null;
        if ($model) {
            Cache::forget("SystemVersion_isactive_" . $model->platform_id);
        }
        return $result;
    }
}
