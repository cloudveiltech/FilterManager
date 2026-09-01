<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use App\Models\GroupFilterAssignment;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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
                'label' => 'Updated At',
                'type' => 'datetime',
                'name' => 'updated_at'
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
        $filename = 'export.zip';
        $category = '';
        if ($request->has('file')) {
            $filename = $request->input('file');
            $filename = preg_replace('/[^0-9a-zA-Z\-_\.]+/', '', $filename);
            $category = Str::after(Str::before($filename, '.zip'), 'export_');
        }

        try {
            $exportDisk = Storage::disk(FilterImportGate::EXPORT_DISK);
            $gate = app()->makeWith(FilterImportGate::class, [
                'exportDisk' => $exportDisk,
            ]);
        } catch (Throwable $exception) {
            return response(
                "Unable to import category [{$category}]: export disk error: {$exception->getMessage()}",
                500,
            );
        }

        $decision = $gate->decide($filename);

        if ($decision?->outcome === FilterImportOutcome::DENIED) {
            return response(
                'Import refused for category ['.$category.']: '.($decision->reason ?? 'The category is denied.'),
                403,
            );
        }

        if ($decision?->outcome === FilterImportOutcome::OBJECT_MISSING) {
            return response(
                'Unable to import category ['.$category.']: '.($decision->reason ?? 'The export object is missing.'),
                404,
            );
        }

        if ($decision?->outcome === FilterImportOutcome::DISK_ERROR) {
            return response(
                'Unable to import category ['.$category.']: export disk error: '.($decision->reason ?? 'Unknown disk error.'),
                500,
            );
        }

        if ($decision === null) {
            if ($filename !== 'export.zip') {
                return response("Unable to derive a category from export object [{$filename}].", 400);
            }

            try {
                if (! $exportDisk->exists($filename)) {
                    return response(
                        'Unable to import the all-category export: the bucket object does not exist on the export disk.',
                        404,
                    );
                }
            } catch (Throwable $exception) {
                return response(
                    'Unable to import the all-category export: export disk error: '.$exception->getMessage(),
                    500,
                );
            }
        }

        // Adoption intentionally bypasses the allowlist and current-file check. The gate is
        // used above to retain its deny policy and export-disk/object validation.
        try {
            ProcessTextFilterArchiveUpload::dispatch(
                FilterImportGate::DEFAULT_NAMESPACE,
                $filename,
                true,
                $category,
                FilterImportGate::EXPORT_DISK,
            );
        } catch (Throwable $exception) {
            return response(
                'Unable to dispatch import for category ['.$category.']: '.$exception->getMessage(),
                500,
            );
        }

        return response(
            'Import has been triggered for category ['.$category.'] from export disk object ['.$filename.'].',
        );
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
}
