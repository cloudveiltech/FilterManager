<?php

namespace App\Http\Controllers\Admin;

use App\Models\AppGroup;
use App\Models\AppGroupToApp;
use App\Models\SystemPlatform;
use App\Models\UserGroupToAppGroup;
use App\Http\Controllers\Admin\Traits\RebuildsGroupData;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AppGroupCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AppGroupCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use RebuildsGroupData;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\AppGroup::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/app-group');
        CRUD::setEntityNameStrings('app group', 'app groups');
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
                'name' => 'group_name',
                'priority' => 1,
            ],
            [
                'label' => 'Linked Apps',
                'type' => 'text',
                'name' => 'apps',
                'limit' => 200000,
                'priority' => 1,
                'value' => function ($entry) {
                    $apps = $entry->apps;
                    return $apps->pluck("name")->sort()->join(", ");
                },
                'wrapper' => [
                    'style' => 'white-space: normal',
                ]
            ],
            [
                'label' => 'Updated at',
                'type' => 'datetime',
                'name' => 'updated_at',
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
            'group_name' => 'required|min:2',
        ]);

        $this->crud->addFields([
            [
                'label' => 'Name',
                'type' => 'text',
                'name' => 'group_name',
            ],
            [
                'label' => 'Linked Apps',
                'type' => 'select2_multiple',
                'name' => 'apps',
                'entity' => 'apps',
                'attribute' => 'name',
                'model' => 'App\Models\App',
                'pivot' => true,
                'options' => (function ($query) {
                    return $query->orderBy('name', 'ASC')->get();
                })
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

    public function store()
    {
        $result = $this->traitStore();
        $this->rebuildGroupsForEntry();

        return $result;
    }

    public function update()
    {
        $result = $this->traitUpdate();
        $this->rebuildGroupsForEntry();

        return $result;
    }

    public function destroy($id)
    {
        // Collected before the pivot rows go away, so we still know which groups to rebuild.
        $userGroupIds = $this->userGroupIdsForAppGroups([$id]);

        AppGroupToApp::where('app_group_id', $id)->delete();
        UserGroupToAppGroup::where('app_group_id', $id)->delete();
        $applicationGroup = AppGroup::where('id', $id)->first();
        if (!is_null($applicationGroup)) {
            $applicationGroup->delete();
        }

        $this->rebuildGroups($userGroupIds);

        return true;
    }

    private function rebuildGroupsForEntry(): void
    {
        $entry = $this->data['entry'] ?? null;

        if ($entry) {
            $this->rebuildGroups($this->userGroupIdsForAppGroups([$entry->id]));
        }
    }
}
