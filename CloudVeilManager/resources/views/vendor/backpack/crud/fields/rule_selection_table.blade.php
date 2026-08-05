{{-- Rule selection table field --}}
{{--
    Self-contained, reusable field: one row per FilterList with a single status control. On submit,
    JS serializes every row whose status differs from the default into a single hidden input — one
    form variable regardless of list count, so the POST can't be truncated by PHP's max_input_vars,
    and DataTables detaching off-page rows can't drop inputs (we read all rows via the DataTables
    API, not the DOM).

    Used in two places:
    - Group "Rule Selection" (the defaults below): statuses blacklist/whitelist/bypass posted as
      rule_status_json; GroupCrudController::store()/update() json_decode the map and sync() the
      Group::assignedFilters() relation into group_filter_assignments.
    - User/Activation "Category Overrides": statuses Blacklist/Whitelist/BypassList/Ignored posted
      under the model attribute name (category_overrides); the model mutator stores them in
      config_override.CategoryOverrides.

    Field options:
    - filter_lists          collection of FilterList rows (id, category, type) to render
    - input_name            name of the single hidden input (defaults to the field name)
    - statuses              ordered map of status value => select label (excluding the default)
    - default_status        the "no selection" value; rendered blank, omitted from the payload
    - filter_labels         optional map of status value => label for the status filter dropdown
    - default_filter_label  label for the default status in the filter dropdown
    - status_colors         map of status value => text color for the status control
    - prefill               'assigned_filters' to prefill from $entry->assignedFilters pivot flags;
                            otherwise the field's value (an id => status map) is used

    Search filters the Category column only. A single-select status dropdown filters rows by their
    current status. The status column sorts by status. Persistence happens only on form Save.
--}}
@php
    $filterLists = $field['filter_lists'] ?? collect();
    $tableId = 'rule_selection_table_' . \Illuminate\Support\Str::random(6);

    // Configuration, defaulting to the Group rule-selection behavior.
    $inputName = $field['input_name'] ?? $field['name'];
    $statuses = $field['statuses'] ?? ['blacklist' => 'Blacklist', 'whitelist' => 'Whitelist', 'bypass' => 'Bypass'];
    $defaultStatus = $field['default_status'] ?? 'ignored';
    $filterLabels = $field['filter_labels']
        ?? $field['statuses']
        ?? ['blacklist' => 'Blacklists', 'whitelist' => 'Whitelists', 'bypass' => 'Bypassed'];
    $defaultFilterLabel = $field['default_filter_label'] ?? 'Ignored';
    $statusColors = $field['status_colors'] ?? ['blacklist' => '#d63939', 'whitelist' => '#1f9d57', 'bypass' => '#d9a406'];

    // Sort rank for the status column, in the order the statuses were declared (default sorts last).
    $rankMap = array_flip(array_keys($statuses));
    $rankMap[$defaultStatus] = 99;

    // Prefill: old() input wins (validation error re-render), else the saved value. An empty map is
    // still a valid "all default" submission, so key off presence of the input, not truthiness.
    $hasOld = old($inputName) !== null;
    $oldStatuses = $hasOld ? (json_decode(old($inputName), true) ?: []) : [];
    $currentStatuses = [];
    if (!$hasOld) {
        if (($field['prefill'] ?? null) === 'assigned_filters' && isset($entry) && $entry) {
            foreach ($entry->assignedFilters as $assigned) {
                $p = $assigned->pivot;
                $currentStatuses[$assigned->id] = $p->as_blacklist ? 'blacklist'
                    : ($p->as_whitelist ? 'whitelist'
                    : ($p->as_bypass ? 'bypass' : $defaultStatus));
            }
        } elseif (isset($field['value']) && is_array($field['value'])) {
            // e.g. a model accessor returning a filter_list_id => status map (category overrides)
            $currentStatuses = $field['value'];
        }
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{{ $field['label'] ?? 'Rule Selection' }}</label>

    <div class="rule-selection-field" data-init="0" data-table="#{{ $tableId }}"
         data-default-status="{{ $defaultStatus }}"
         data-status-ranks="{{ json_encode($rankMap, JSON_FORCE_OBJECT) }}">

        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <input type="text" class="form-control rule-search"
                       placeholder="Search category…" autocomplete="off">
            </div>
            <div class="col-md-3 mb-2">
                <select class="form-select rule-status-filter">
                    <option value="">All statuses</option>
                    @foreach ($filterLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                    <option value="{{ $defaultStatus }}">{{ $defaultFilterLabel }}</option>
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
                        $status = $oldStatuses[$list->id] ?? $currentStatuses[$list->id] ?? $defaultStatus;
                        $rank = $rankMap[$status] ?? 99;
                    @endphp
                    <tr data-list-id="{{ $list->id }}" data-status="{{ $status }}">
                        <td>{{ $list->category }}</td>
                        <td>{{ $isTrigger ? 'Trigger' : 'Filter' }}</td>
                        {{-- data-order drives the (dynamic) sort of the status column; JS keeps it in sync --}}
                        <td class="rule-status-cell" data-order="{{ $rank }}">
                            {{-- No name: values are gathered into the hidden input on submit (below). --}}
                            <select class="form-select form-select-sm rule-status-select">
                                <option value="{{ $defaultStatus }}" @selected($status === $defaultStatus)></option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Single form variable for the whole table. JS fills it on submit; the value here keeps a
             valid payload if JS never runs (e.g. save before boot) and preserves old() on re-render. --}}
        <input type="hidden" name="{{ $inputName }}" class="rule-status-json"
               value="{{ $hasOld ? old($inputName) : json_encode(array_filter($currentStatuses, fn ($s) => $s !== $defaultStatus), JSON_FORCE_OBJECT) }}">
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

        @foreach ($statusColors as $value => $color)
        table.rule-selection-datatable tr[data-status="{{ $value }}"] .rule-status-select { color: {{ $color }}; font-weight: 600; }
        @endforeach
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

                // Per-instance config: sort rank per status, and the default ("no selection")
                // status, whose rows are omitted from the payload.
                var STATUS_RANK = JSON.parse(root.getAttribute('data-status-ranks'));
                var DEFAULT_STATUS = root.getAttribute('data-default-status');

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

                // Serialize every non-default row into the single hidden input. Reads via the
                // DataTables API (dt.rows().nodes()) rather than the DOM, so rows detached by
                // pagination are still included — that's the whole point of the JSON payload.
                function serialize() {
                    var map = {};
                    dt.rows().nodes().to$().find('.rule-status-select').each(function () {
                        var v = this.value;
                        if (v !== DEFAULT_STATUS) {
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
                    $tr.find('.rule-status-cell').attr('data-order', rank == null ? 99 : rank);
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
