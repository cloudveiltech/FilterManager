<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Jobs\RebuildGroupData;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use App\Models\Group;
use App\Models\GroupFilterAssignment;
use App\Services\GroupFilterAssignmentService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class FilterListCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FilterListCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\BulkDeleteOperation { bulkDelete as traitBulkDelete; }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\FilterList::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/filter-list');
        CRUD::setEntityNameStrings('filter list', 'filter lists');
        CRUD::allowAccess('groups');
    }

    protected function setupBulkAssignGroupsRoutes($segment, $routeName, $controller)
    {
        Route::post($segment . '/bulk-assign-groups', [
            'as' => $routeName . '.bulkAssignGroups',
            'uses' => $controller . '@bulkAssignGroups',
            'operation' => 'bulkAssignGroups',
        ]);
    }

    protected function setupBulkAssignGroupsDefaults()
    {
        CRUD::allowAccess('bulkAssignGroups');

        CRUD::operation('bulkAssignGroups', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });

        CRUD::operation('list', function () {
            CRUD::enableBulkActions();
            CRUD::addButton('bottom', 'bulk_assign_groups', 'view', 'crud::buttons.bulk_assign_groups', 'beginning')
                ->meta([
                    'groups' => Group::query()->orderBy('name')->get(['id', 'name']),
                ]);
        });
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->query
            ->withCount('groups')
            ->with('groups');

        $this->crud->setColumns([
            [
                'label' => 'Category Name',
                'type' => 'text',
                'name' => 'category',
            ],
            [
                'label' => 'List Group Name',
                'type' => 'text',
                'name' => 'namespace',
            ],
            [
                'label' => 'Type',
                'type' => 'text',
                'name' => 'type',
            ],
            [
                'label' => '# Entries',
                'type' => 'number',
                'name' => 'entries_count',
            ],
            [
                'label' => 'Groups',
                'type' => 'number',
                'name' => 'groups_count',
                'searchLogic' => false,
                'orderable' => true,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->orderBy('groups_count', $columnDirection);
                },
            ],
            [
                'label' => 'Group Assignments',
                'type' => 'text',
                'name' => 'groups',
                'limit' => 200000,
                'searchLogic' => false,
                'orderable' => false,
                'value' => function ($entry) {
                    $assignmentService = app(GroupFilterAssignmentService::class);

                    return $entry->groups
                        ->map(function ($group) use ($assignmentService) {
                            $status = ucfirst($assignmentService->statusFromPivot($group->pivot));

                            return $group->name . ' (' . $status . ')';
                        })
                        ->sort()
                        ->join(', ');
                },
                'wrapper' => [
                    'style' => 'white-space: normal',
                ],
            ],
            [
                'label' => 'Updated At',
                'type' => 'datetime',
                'name' => 'updated_at'
            ],
        ]);

        CRUD::filter('assigned_group')
            ->type('select2')
            ->label('Assigned to group')
            ->values(function () {
                return Group::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->whenActive(function ($value) {
                CRUD::addClause('whereHas', 'groups', function ($query) use ($value) {
                    $query->where('groups.id', $value);
                });
            });

        CRUD::filter('assignment_status')
            ->type('dropdown')
            ->label('Assignment status')
            ->values([
                'blacklist' => 'Blacklist',
                'whitelist' => 'Whitelist',
                'bypass' => 'Bypass',
            ])
            ->whenActive(function ($value) {
                $pivotColumn = [
                    'blacklist' => 'as_blacklist',
                    'whitelist' => 'as_whitelist',
                    'bypass' => 'as_bypass',
                ][$value] ?? null;

                if ($pivotColumn === null) {
                    return;
                }

                CRUD::addClause('whereHas', 'groups', function ($query) use ($pivotColumn) {
                    $query->where('group_filter_assignments.' . $pivotColumn, 1);
                });
            });

        $this->crud->button('groups')->stack('line')->view('crud::buttons.quick')->meta([
            'wrapper' => [
                'href' => function ($entry, $crud) {
                    return backpack_url('filter-list/' . $entry->getKey() . '/groups');
                },
            ],
            'icon' => 'la la-users',
            'access' => true,
            'label' => 'Groups',
        ]);
    }

    public function groups($id)
    {
        CRUD::hasAccessOrFail('groups');

        $filterList = FilterList::with('groups')->findOrFail($id);

        return $this->renderGroupAssignments($filterList);
    }

    public function updateGroups(Request $request, $id, GroupFilterAssignmentService $assignmentService)
    {
        CRUD::hasAccessOrFail('groups');

        $filterList = FilterList::findOrFail($id);
        $statuses = $assignmentService->statusesFromRequest($request, 'group_status_json');
        $affectedGroups = $assignmentService->syncFilterListAssignments($filterList, $statuses);

        if ($affectedGroups !== []) {
            RebuildGroupData::dispatch($affectedGroups);
        }

        \Alert::success('Group assignments updated.')->flash();

        return redirect()->route('filter-list.groups', ['id' => $filterList->getKey()]);
    }

    private function renderGroupAssignments(FilterList $filterList)
    {
        $this->data['crud'] = $this->crud;
        $this->data['entry'] = $filterList;
        $this->data['filterList'] = $filterList;
        $this->data['field'] = [
            'name' => 'group_status_json',
            'type' => 'status_assignment_table',
            'label' => 'Groups',
            'input_name' => 'group_status_json',
            'current_relation' => 'groups',
            'row_label' => 'Group',
            'show_sublabel' => false,
            'rows' => Group::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'label' => $group->name,
                        'sublabel' => null,
                    ];
                }),
        ];

        $this->data['title'] = 'Group assignments';

        return view('crud::filter_list_groups', $this->data);
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
            'overwrite' => 'required|boolean',
            'namespace' => 'required|string|min:1|max:64',
        ]);

        $this->crud->addFields([
                [
                    'name' => 'file',
                    'label' => 'Select file',
                    'type' => 'upload',
                    'upload'    => true,
                ],
                [
                    'name' => 'namespace',
                    'label' => "Collection Name",
                    'type' => 'text',
                    'default' => 'Default',
                ],
                [
                    'name' => 'overwrite',
                    'label' => "Overwrite",
                    'type' => 'switch',
                ]
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

    public function store()
    {
        $response = $this->processUploadedFilterLists();
        if ($response) {
            return $response;
        }

        return Redirect::to(CRUD::getRoute());
    }

    public function triggerUpdate(Request $request)
    {
        $timestamp = Carbon::now()->toIso8601ZuluString();
        $client = new Client();
        $filename = 'export.zip';
        $category = '';
        if ($request->has('file')) {
            $filename = $request->input('file');
            $filename = preg_replace('/[^0-9a-zA-Z\-_\.]+/', '', $filename);
            $category = Str::after(Str::before($filename, '.zip'), 'export_');
        }
        $results = 'Downloading File from ' . config('app.default_list_export_url') . $filename . '<br>';
        $response = $client->get(config('app.default_list_export_url') . $filename);
        $results .= 'Saving to: ' . $timestamp . '.zip<br>';
        Storage::put('export' . $timestamp . '.zip', $response->getBody());
        $file = Storage::size('export' . $timestamp . '.zip');
        ProcessTextFilterArchiveUpload::dispatch('default', storage_path('app/export' . $timestamp . '.zip'), true, $category);
        $results .= 'File is : ' . $file . ' bytes.<br>';
        $results .= 'Import has been triggered.<br>';
        return response($results);
    }


    public function destroy($id)
    {
        $affectedGroups = [];

        $existingList = FilterList::where('id', $id)->first();
        $filterRulesManager = new FilterRulesManager();
        if (!is_null($existingList)) {
            $filterRulesManager->deleteFiles($existingList);
            // Pull group assignment of this filter, if any, then delete them.
            $affectedGroups = array_merge($affectedGroups, ProcessTextFilterArchiveUpload::getGroupsAttachedToFilterId($existingList->id));
            GroupFilterAssignment::where('filter_list_id', $existingList->id)->delete();

            // It was only a text list, so just delete this entry.
            $existingList->delete();
        }

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        ProcessTextFilterArchiveUpload::forceRebuildOnGroups($affectedGroups);

        return true;
    }


    public function processUploadedFilterLists()
    {
        CRUD::setRequest(CRUD::validateRequest());
        $request = CRUD::getRequest();
        $request->request->remove('file');
        CRUD::setRequest($request);
        CRUD::unsetValidation();

        $listFile = $request->file();
        $shouldOverwrite = $request->get('overwrite');
        $listNamespace = preg_replace('/\s+/', '_', strtolower($request->get('namespace')));

        $success = false;

        foreach ($listFile as $file) {
            switch (strtolower($file->getClientOriginalExtension())) {
                case 'zip':
                    {
                        $storedFile = $file->store('zip_uploads');
                        $success = ProcessTextFilterArchiveUpload::dispatch(
                            $listNamespace,
                            storage_path('app/' . $storedFile),
                            $shouldOverwrite
                        );
                    }
                    break;
            }
        }

        if (!$success) {
            response('Failure while processing uploaded file.', 500);
        }

        return null;
    }

    public function bulkDelete()
    {
        $this->crud->hasAccessOrFail('bulkDelete');

        $entries = request()->input('entries', []);
        $deletedEntries = [];

        foreach ($entries as $key => $id) {
            $this->destroy($id);
            $deletedEntries[] = $id;
        }

        return $deletedEntries;
    }

    public function bulkAssignGroups(Request $request, GroupFilterAssignmentService $assignmentService)
    {
        $this->crud->hasAccessOrFail('bulkAssignGroups');

        $status = $request->input('assignment_status');
        $filterListIds = collect((array) $request->input('entries', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        $groupIds = Group::query()
            ->whereKey(collect((array) $request->input('group_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if (!in_array($status, [...GroupFilterAssignmentService::ACTIVE_STATUSES, 'clear'], true)) {
            return response()->json(['message' => 'Choose an assignment status.'], 422);
        }

        if ($filterListIds->isEmpty()) {
            return response()->json(['message' => 'Select at least one filter list.'], 422);
        }

        if ($groupIds->isEmpty()) {
            return response()->json(['message' => 'Select at least one group.'], 422);
        }

        $targetStatuses = array_fill_keys(
            $groupIds->all(),
            $status === 'clear' ? 'ignored' : $status
        );
        $affectedGroups = [];
        $updatedFilterLists = 0;

        foreach (FilterList::whereKey($filterListIds)->get() as $filterList) {
            if (!$this->crud->hasAccess('bulkAssignGroups', $filterList)) {
                continue;
            }

            $changedGroups = $assignmentService->updateFilterListGroupAssignments(
                $filterList,
                $targetStatuses
            );

            if ($changedGroups !== []) {
                $updatedFilterLists++;
                $affectedGroups = array_merge($affectedGroups, $changedGroups);
            }
        }

        $affectedGroups = array_values(array_unique(array_map('intval', $affectedGroups)));

        if ($affectedGroups !== []) {
            RebuildGroupData::dispatch($affectedGroups);
        }

        return response()->json([
            'updated_filter_lists' => $updatedFilterLists,
            'affected_groups' => count($affectedGroups),
        ]);
    }
}
