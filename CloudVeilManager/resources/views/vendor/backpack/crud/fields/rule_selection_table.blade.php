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
                        <td>{{ $isTrigger ? 'Trigger' : 'Filter' }}</td>
                        {{-- data-order drives the (dynamic) sort of the status column; JS keeps it in sync --}}
                        <td class="rule-status-cell" data-order="9">
                            <select class="form-select form-select-sm rule-status-select" data-list-id="{{ $list->id }}">
                                <option value="ignored"></option>
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
    <style>
        /* Borderless, transparent status control — colored text only, keyed off the row's status
           (tr[data-status], kept in sync by JS). Tabler's .form-select rules tie on specificity and
           load after ours, so force with !important. */
        select.rule-status-select { border: 0 !important; box-shadow: none !important; background-color: transparent !important; outline: none !important; }

        table.rule-selection-datatable tbody td:first-child { font-weight: 600; }

        tr[data-status="blacklist"] .rule-status-select { color: #d63939; font-weight: 600; }
        tr[data-status="whitelist"] .rule-status-select { color: #1f9d57; font-weight: 600; }
        tr[data-status="bypass"]    .rule-status-select { color: #d9a406; font-weight: 600; }
    </style>
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

                // Sort rank for the status column (blank/ignored sorts last).
                var STATUS_RANK = { blacklist: 0, whitelist: 1, bypass: 2, ignored: 9 };

                // Sync a row's visible state (row data-status, the control's value + color class,
                // and the status cell's data-order used for sorting). DOM-only; safe pre-DataTables.
                function applyRowVisual($tr, status) {
                    $tr.attr('data-status', status); // drives the color CSS
                    $tr.find('.rule-status-select').val(status);
                    var rank = STATUS_RANK[status];
                    $tr.find('.rule-status-cell').attr('data-order', rank == null ? 9 : rank);
                }

                // Initialize each row's control + data-status from the hidden selects.
                var initial = readInitialStatuses();
                $table.find('tbody tr').each(function () {
                    var $tr = $(this);
                    var listId = String($tr.data('list-id'));
                    applyRowVisual($tr, initial[listId] || 'ignored');
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
                        // Type + Status columns aren't full-text searchable; Status sorts by data-order.
                        { targets: [1, 2], searchable: false },
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

                // Row status change -> update hidden selects, row visuals, status-sort key, and
                // re-apply search/filter/sort (draw(false) preserves the current page length).
                $table.on('change', '.rule-status-select', function () {
                    var $select = $(this);
                    var $tr = $select.closest('tr');
                    var listId = $select.data('list-id');
                    var status = $select.val();
                    applyRowVisual($tr, status);
                    applyStatus(listId, status);
                    dt.cell($tr.find('.rule-status-cell')[0]).invalidate();
                    dt.draw(false);
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
