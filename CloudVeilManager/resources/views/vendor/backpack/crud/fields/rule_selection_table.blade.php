{{-- Rule selection table field --}}
{{--
    Self-contained field. Renders one row per FilterList with a single status control
    (Blacklist / Whitelist / Bypass / blank). On submit, JS serializes every non-ignored row into a
    single hidden input (rule_status_json) — one form variable regardless of list count, so the POST
    can't be truncated by PHP's max_input_vars, and DataTables detaching off-page rows can't drop
    inputs (we read all rows via the DataTables API, not the DOM). GroupCrudController::store()/update()
    json_decode that map and sync() the single Group::assignedFilters() relation into
    group_filter_assignments — no per-row inputs, no hidden relationship fields, no patchRules().

    Search filters the Category column only. A single-select status dropdown filters rows by their
    current status. The status column sorts by status. Persistence happens only on group form Save.
--}}
@php
    $filterLists = $field['filter_lists'] ?? collect();
    $tableId = 'rule_selection_table_' . \Illuminate\Support\Str::random(6);

    // Sort rank for the status column (blank/ignored sorts last).
    $rankMap = ['blacklist' => 0, 'whitelist' => 1, 'bypass' => 2, 'ignored' => 9];

    // Prefill: old() input wins (validation error re-render), else the group's saved assignments.
    // old() now carries the JSON map (rule_status_json); an empty map is still a valid "all ignored"
    // submission, so key off presence of the field, not truthiness of the decoded array.
    $hasOld = old('rule_status_json') !== null;
    $oldStatuses = $hasOld ? (json_decode(old('rule_status_json'), true) ?: []) : [];
    $currentStatuses = [];
    if (!$hasOld && isset($entry) && $entry) {
        foreach ($entry->assignedFilters as $assigned) {
            $p = $assigned->pivot;
            $currentStatuses[$assigned->id] = $p->as_blacklist ? 'blacklist'
                : ($p->as_whitelist ? 'whitelist'
                : ($p->as_bypass ? 'bypass' : 'ignored'));
        }
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>Rule Selection</label>

    <div class="rule-selection-field" data-init="0" data-table="#{{ $tableId }}">

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
                        $status = $oldStatuses[$list->id] ?? $currentStatuses[$list->id] ?? 'ignored';
                        $rank = $rankMap[$status] ?? 9;
                    @endphp
                    <tr data-list-id="{{ $list->id }}" data-status="{{ $status }}">
                        <td>{{ $list->category }}</td>
                        <td>{{ $isTrigger ? 'Trigger' : 'Filter' }}</td>
                        {{-- data-order drives the (dynamic) sort of the status column; JS keeps it in sync --}}
                        <td class="rule-status-cell" data-order="{{ $rank }}">
                            {{-- No name: values are gathered into rule_status_json on submit (below). --}}
                            <select class="form-select form-select-sm rule-status-select">
                                <option value="ignored" @selected($status === 'ignored')></option>
                                <option value="blacklist" @selected($status === 'blacklist')>Blacklist</option>
                                <option value="whitelist" @selected($status === 'whitelist')>Whitelist</option>
                                <option value="bypass" @selected($status === 'bypass')>Bypass</option>
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Single form variable for the whole table. JS fills it on submit; the value here keeps a
             valid payload if JS never runs (e.g. save before boot) and preserves old() on re-render. --}}
        <input type="hidden" name="rule_status_json" class="rule-status-json"
               value="{{ $hasOld ? old('rule_status_json') : json_encode(array_filter($currentStatuses, fn ($s) => $s !== 'ignored')) }}">
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
                var $table = $(root.getAttribute('data-table'));
                var $hidden = $root.find('.rule-status-json');

                // Sort rank for the status column (blank/ignored sorts last).
                var STATUS_RANK = { blacklist: 0, whitelist: 1, bypass: 2, ignored: 9 };

                // DataTables: search Category column only; status column sorts by data-order.
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

                // Serialize every non-ignored row into the single hidden input. Reads via the
                // DataTables API (dt.rows().nodes()) rather than the DOM, so rows detached by
                // pagination are still included — that's the whole point of the JSON payload.
                function serialize() {
                    var map = {};
                    dt.rows().nodes().to$().find('.rule-status-select').each(function () {
                        var v = this.value;
                        if (v && v !== 'ignored') {
                            map[this.closest('tr').getAttribute('data-list-id')] = v;
                        }
                    });
                    $hidden.val(JSON.stringify(map));
                }

                // Keep the payload current on every change, and rebuild once more at submit time as the
                // authoritative capture (covers any row not touched since boot).
                serialize();
                $root.closest('form').on('submit', serialize);

                // Row status change -> keep the row's data-status (drives color + filter) and the
                // status-sort key in sync, refresh the payload, then redraw.
                $table.on('change', '.rule-status-select', function () {
                    var $select = $(this);
                    var $tr = $select.closest('tr');
                    var status = $select.val();
                    var rank = STATUS_RANK[status];
                    $tr.attr('data-status', status);
                    $tr.find('.rule-status-cell').attr('data-order', rank == null ? 9 : rank);
                    dt.cell($tr.find('.rule-status-cell')[0]).invalidate();
                    dt.draw(false);
                    serialize();
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
