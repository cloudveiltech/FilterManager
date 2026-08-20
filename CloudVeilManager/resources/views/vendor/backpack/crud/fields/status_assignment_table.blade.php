{{-- Reusable status assignment table field --}}
@php
    $rows = collect($field['rows'] ?? []);
    $inputName = $field['input_name'] ?? $field['name'] ?? 'rule_status_json';
    $tableId = 'status_assignment_table_' . \Illuminate\Support\Str::random(6);
    $rankMap = ['blacklist' => 0, 'whitelist' => 1, 'bypass' => 2, 'ignored' => 9];
    $hasOld = old($inputName) !== null;
    $oldValue = (string) old($inputName, '');
    $oldStatuses = $hasOld ? (json_decode($oldValue, true) ?: []) : [];
    $currentStatuses = [];
    $currentRelation = $field['current_relation'] ?? null;

    if (!$hasOld && isset($entry) && $entry && $currentRelation) {
        $assignmentService = app(\App\Services\GroupFilterAssignmentService::class);

        foreach ($entry->{$currentRelation} as $assigned) {
            if (isset($assigned->pivot)) {
                $currentStatuses[$assigned->getKey()] = $assignmentService->statusFromPivot($assigned->pivot);
            }
        }
    }

    $showSublabel = $field['show_sublabel'] ?? $rows->contains(function ($row) {
        return data_get($row, 'sublabel') !== null && data_get($row, 'sublabel') !== '';
    });
    $statusColumn = $showSublabel ? 2 : 1;
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{{ $field['label'] ?? 'Status Assignments' }}</label>

    <div class="status-assignment-field" data-init="0" data-table="#{{ $tableId }}"
         data-status-column="{{ $statusColumn }}">

        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <input type="text" class="form-control status-assignment-search"
                       placeholder="{{ $field['search_placeholder'] ?? 'Search…' }}" autocomplete="off">
            </div>
            <div class="col-md-3 mb-2">
                <select class="form-select status-assignment-filter">
                    <option value="">All statuses</option>
                    <option value="blacklist">Blacklists</option>
                    <option value="whitelist">Whitelists</option>
                    <option value="bypass">Bypassed</option>
                    <option value="ignored">Ignored</option>
                </select>
            </div>
        </div>

        <table id="{{ $tableId }}" class="table table-sm table-striped status-assignment-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>{{ $field['row_label'] ?? 'Item' }}</th>
                    @if ($showSublabel)
                        <th>{{ $field['row_sublabel'] ?? 'Details' }}</th>
                    @endif
                    <th style="width: 180px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $rowId = data_get($row, 'id');
                        $status = $oldStatuses[$rowId] ?? $currentStatuses[$rowId] ?? 'ignored';
                        $rank = $rankMap[$status] ?? 9;
                    @endphp
                    <tr data-assignment-id="{{ $rowId }}" data-status="{{ $status }}">
                        <td>{{ data_get($row, 'label') }}</td>
                        @if ($showSublabel)
                            <td>{{ data_get($row, 'sublabel') }}</td>
                        @endif
                        <td class="status-assignment-status-cell" data-order="{{ $rank }}">
                            <select class="form-select form-select-sm status-assignment-select">
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

        <input type="hidden" name="{{ $inputName }}" class="status-assignment-json"
               value="{{ $hasOld ? $oldValue : json_encode(array_filter($currentStatuses, fn ($status) => $status !== 'ignored')) }}">
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    @basset('https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css')
    <style>
        select.status-assignment-select { border: 0 !important; box-shadow: none !important; background-color: transparent !important; outline: none !important; }

        table.status-assignment-datatable tbody td:first-child { font-weight: 600; }

        tr[data-status="blacklist"] .status-assignment-select { color: #d63939; font-weight: 600; }
        tr[data-status="whitelist"] .status-assignment-select { color: #1f9d57; font-weight: 600; }
        tr[data-status="bypass"]    .status-assignment-select { color: #d9a406; font-weight: 600; }
    </style>
@endpush

@push('crud_fields_scripts')
    @basset('https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js')
    @basset('https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js')

    @bassetBlock('cloudveil/fields/status-assignment-table.js')
    <script>
        (function () {
            function initStatusAssignmentField(root) {
                if (root.getAttribute('data-init') === '1') {
                    return;
                }
                root.setAttribute('data-init', '1');

                var $ = window.jQuery;
                var $root = $(root);
                var $table = $(root.getAttribute('data-table'));
                var $hidden = $root.find('.status-assignment-json');
                var statusColumn = parseInt(root.getAttribute('data-status-column'), 10);
                var nonLabelColumns = [];

                for (var index = 1; index <= statusColumn; index++) {
                    nonLabelColumns.push(index);
                }

                var STATUS_RANK = { blacklist: 0, whitelist: 1, bypass: 2, ignored: 9 };
                var dt = $table.DataTable({
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
                    ordering: true,
                    order: [[0, 'asc']],
                    columnDefs: [
                        { targets: nonLabelColumns, searchable: false },
                    ],
                    dom: 'rltip',
                });

                $root.find('.status-assignment-search').on('keyup change', function () {
                    dt.column(0).search(this.value).draw();
                });

                var activeStatusFilter = '';
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    if (settings.nTable !== $table[0]) {
                        return true;
                    }
                    if (!activeStatusFilter) {
                        return true;
                    }
                    var row = settings.aoData[dataIndex].nTr;
                    return row.getAttribute('data-status') === activeStatusFilter;
                });
                $root.find('.status-assignment-filter').on('change', function () {
                    activeStatusFilter = this.value;
                    dt.draw();
                });

                function serialize() {
                    var map = {};
                    dt.rows().nodes().to$().find('.status-assignment-select').each(function () {
                        var value = this.value;
                        if (value && value !== 'ignored') {
                            map[this.closest('tr').getAttribute('data-assignment-id')] = value;
                        }
                    });
                    $hidden.val(JSON.stringify(map));
                }

                serialize();
                $root.closest('form').on('submit', serialize);

                $table.on('change', '.status-assignment-select', function () {
                    var $select = $(this);
                    var $tr = $select.closest('tr');
                    var status = $select.val();
                    var rank = STATUS_RANK[status];
                    $tr.attr('data-status', status);
                    $tr.find('.status-assignment-status-cell').attr('data-order', rank == null ? 9 : rank);
                    dt.cell($tr.find('.status-assignment-status-cell')[0]).invalidate();
                    dt.draw(false);
                    serialize();
                });
            }

            function boot() {
                document.querySelectorAll('.status-assignment-field').forEach(initStatusAssignmentField);
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
