{{-- Rule selection table field --}}
{{--
    Renders one row per FilterList with a single status control
    (Blacklist / Whitelist / Bypass / Ignored). The control drives the three hidden
    `relationship` select2 fields (assignedBlacklistFilters / assignedWhitelistFilters /
    assignedBypassFilters) rendered elsewhere on this tab, which the existing
    GroupCrudController::patchRules() + Backpack pivot sync consume unchanged.

    Search filters the Category column only. A single-select status dropdown filters rows
    by their current status. Persistence happens only on the group form Save.
--}}
@php
    $filterLists = $field['filter_lists'] ?? collect();
    $tableId = 'rule_selection_table_' . \Illuminate\Support\Str::random(6);
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>Rule Selection</label>

    <div class="rule-selection-field" data-init="0"
         data-table="#{{ $tableId }}"
         data-blacklist-select="assignedBlacklistFilters[]"
         data-whitelist-select="assignedWhitelistFilters[]"
         data-bypass-select="assignedBypassFilters[]">

        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <input type="text" class="form-control rule-search"
                       placeholder="Search category…" autocomplete="off">
            </div>
            <div class="col-md-3 mb-2">
                <select class="form-select rule-status-filter">
                    <option value="">All statuses</option>
                    <option value="blacklist">Blacklists</option>
                    <option value="whitelist">Whitelists</option>
                    <option value="bypass">Bypassed</option>
                    <option value="ignored">Ignored</option>
                </select>
            </div>
        </div>

        <table id="{{ $tableId }}" class="table table-sm table-striped rule-selection-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Type</th>
                    <th style="width: 180px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filterLists as $list)
                    @php
                        $isTrigger = $list->type === 'Triggers';
                    @endphp
                    <tr data-list-id="{{ $list->id }}" data-status="ignored">
                        <td>{{ $list->category }}</td>
                        <td>
                            <span class="badge {{ $isTrigger ? 'bg-red' : 'bg-green' }}">
                                {{ $isTrigger ? 'Trigger' : 'Filter' }}
                            </span>
                        </td>
                        <td>
                            <select class="form-select form-select-sm rule-status-select" data-list-id="{{ $list->id }}">
                                <option value="ignored">Ignored</option>
                                <option value="blacklist">Blacklist</option>
                                <option value="whitelist">Whitelist</option>
                                <option value="bypass">Bypass</option>
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    @basset('https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css')
@endpush

@push('crud_fields_scripts')
    @basset('https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js')
    @basset('https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js')

    @bassetBlock('cloudveil/fields/rule-selection-table.js')
    <script>
        (function () {
            function initRuleSelectionField(root) {
                if (root.getAttribute('data-init') === '1') {
                    return;
                }
                root.setAttribute('data-init', '1');

                var $ = window.jQuery;
                var $root = $(root);
                var tableSelector = root.getAttribute('data-table');
                var $table = $(tableSelector);

                // The three hidden relationship <select multiple> that actually get submitted.
                var statusSelects = {
                    blacklist: document.querySelector('select[name="' + root.getAttribute('data-blacklist-select') + '"]'),
                    whitelist: document.querySelector('select[name="' + root.getAttribute('data-whitelist-select') + '"]'),
                    bypass: document.querySelector('select[name="' + root.getAttribute('data-bypass-select') + '"]'),
                };

                // Read the current selection out of the hidden selects (honors old() on validation
                // errors and prefilled values on edit). Returns { listId: status }.
                function readInitialStatuses() {
                    var map = {};
                    Object.keys(statusSelects).forEach(function (status) {
                        var sel = statusSelects[status];
                        if (!sel) return;
                        Array.prototype.forEach.call(sel.options, function (opt) {
                            if (opt.selected) {
                                map[String(opt.value)] = status;
                            }
                        });
                    });
                    return map;
                }

                // Drive a hidden select2 relationship select so listId is selected there iff
                // `shouldSelect`, then notify select2/Backpack via change.
                function setHiddenSelectValue(status, listId, shouldSelect) {
                    var sel = statusSelects[status];
                    if (!sel) return;
                    var id = String(listId);
                    var current = $(sel).val() || [];
                    current = current.map(String).filter(function (v) { return v !== id; });
                    if (shouldSelect) {
                        current.push(id);
                    }
                    $(sel).val(current).trigger('change');
                }

                // Apply a status to one list across all three hidden selects (mutually exclusive).
                function applyStatus(listId, status) {
                    ['blacklist', 'whitelist', 'bypass'].forEach(function (s) {
                        setHiddenSelectValue(s, listId, s === status);
                    });
                }

                // Initialize each row's control + data-status from the hidden selects.
                var initial = readInitialStatuses();
                $table.find('tbody tr').each(function () {
                    var $tr = $(this);
                    var listId = String($tr.data('list-id'));
                    var status = initial[listId] || 'ignored';
                    $tr.attr('data-status', status);
                    $tr.find('.rule-status-select').val(status);
                });

                // DataTables: search Category column only; keep pagination.
                var dt = $table.DataTable({
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
                    ordering: true,
                    order: [[0, 'asc']],
                    columnDefs: [
                        { targets: [1, 2], searchable: false },
                        { targets: [2], orderable: false },
                    ],
                    // no default search box (we use our own); 'l' = length menu, 'i' = info, 'p' = pagination
                    dom: 'rltip',
                });

                // Our search box -> filter Category column (index 0) only.
                $root.find('.rule-search').on('keyup change', function () {
                    dt.column(0).search(this.value).draw();
                });

                // Single-select status filter via a DataTables custom search predicate.
                var activeStatusFilter = '';
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    if (settings.nTable !== $table[0]) {
                        return true; // don't affect other tables
                    }
                    if (!activeStatusFilter) {
                        return true;
                    }
                    var row = settings.aoData[dataIndex].nTr;
                    return row.getAttribute('data-status') === activeStatusFilter;
                });
                $root.find('.rule-status-filter').on('change', function () {
                    activeStatusFilter = this.value;
                    dt.draw();
                });

                // Row status change -> update hidden selects, row state, and re-apply the filter.
                $table.on('change', '.rule-status-select', function () {
                    var $select = $(this);
                    var listId = $select.data('list-id');
                    var status = $select.val();
                    $select.closest('tr').attr('data-status', status);
                    applyStatus(listId, status);
                    if (activeStatusFilter) {
                        dt.draw();
                    }
                });
            }

            function boot() {
                document.querySelectorAll('.rule-selection-field').forEach(initRuleSelectionField);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
    @endBassetBlock
@endpush
