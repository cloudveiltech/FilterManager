@if ($crud->hasAccess('bulkAssignGroups') && $crud->get('list.bulkActions'))
    <a href="javascript:void(0)" onclick="bulkAssignGroupsEntries(this); return false;"
       bp-button="bulkAssignGroups" class="btn btn-sm btn-secondary bulk-button">
        <i class="la la-users"></i> <span>Assign groups</span>
    </a>

    <div class="modal fade" id="bulk-assign-groups-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign selected filter lists to groups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bulk-assign-groups-status" class="form-label">Status</label>
                        <select id="bulk-assign-groups-status" class="form-select">
                            <option value="">Choose a status</option>
                            <option value="blacklist">Blacklist</option>
                            <option value="whitelist">Whitelist</option>
                            <option value="bypass">Bypass</option>
                            <option value="clear">Clear assignment</option>
                        </select>
                    </div>
                    <div>
                        <label for="bulk-assign-groups-search" class="form-label">Target groups</label>
                        <input type="search" id="bulk-assign-groups-search" class="form-control mb-2"
                               placeholder="Search groups…" autocomplete="off">
                        <select id="bulk-assign-groups-list" class="form-select" multiple size="10">
                            @foreach ($button->meta['groups'] ?? [] as $group)
                                <option value="{{ $group->getKey() }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Use Ctrl/Cmd-click to select multiple groups.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitBulkAssignGroups()">Apply</button>
                </div>
            </div>
        </div>
    </div>
@endif

@push('after_scripts')
    @bassetBlock('cloudveil/crud/buttons/bulk-assign-groups.js')
    <script>
        if (typeof bulkAssignGroupsEntries !== 'function') {
            function bulkAssignGroupsEntries() {
                if (typeof crud.checkedItems === 'undefined' || crud.checkedItems.length === 0) {
                    new Noty({
                        type: 'warning',
                        text: '<strong>No filter lists selected</strong><br>Select at least one filter list first.'
                    }).show();

                    return;
                }

                $('#bulk-assign-groups-modal').modal('show');
            }

            function submitBulkAssignGroups() {
                var status = $('#bulk-assign-groups-status').val();
                var groups = $('#bulk-assign-groups-list').val() || [];

                if (!status) {
                    new Noty({ type: 'warning', text: 'Choose a status.' }).show();
                    return;
                }

                if (!groups.length) {
                    new Noty({ type: 'warning', text: 'Select at least one target group.' }).show();
                    return;
                }

                $.ajax({
                    url: '{{ url($crud->route) }}/bulk-assign-groups',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        entries: crud.checkedItems,
                        assignment_status: status,
                        group_ids: groups,
                    },
                    success: function (result) {
                        new Noty({
                            type: 'success',
                            text: '<strong>Group assignments updated</strong><br>' +
                                result.updated_filter_lists + ' filter lists changed; ' +
                                result.affected_groups + ' groups queued for rebuild.'
                        }).show();

                        $('#bulk-assign-groups-modal').modal('hide');
                        crud.checkedItems = [];
                        crud.table.draw(false);
                    },
                    error: function (result) {
                        var message = result.responseJSON && result.responseJSON.message
                            ? result.responseJSON.message
                            : 'The bulk assignment could not be applied.';
                        new Noty({ type: 'danger', text: message }).show();
                    }
                });
            }

            $('#bulk-assign-groups-search').on('input', function () {
                var search = this.value.toLowerCase();
                $('#bulk-assign-groups-list option').each(function () {
                    $(this).prop('hidden', this.text.toLowerCase().indexOf(search) === -1);
                });
            });
        }
    </script>
    @endBassetBlock
@endpush
