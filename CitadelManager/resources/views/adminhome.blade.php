@extends('layouts.app') @section('navbar') @include('layouts.components.adminnav') @endsection

@section('styles')
    <link href="{{ asset('css/nouislider.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('plugins/jquery-ui-1.13.2.custom/jquery-ui.css') }}" rel="stylesheet">
@endsection

@section('content')

<!-- Include BaseRecord Record Type JS. Must be done to serve any record type correctly. -->
<script src="{{ asset('js/admin/records/baserecord.js') }}">
</script>
<script src="{{ asset('js/bindings.js') }}"></script>
<script src="{{ asset('js/nouislider.min.js') }}"></script>

<!-- Primary admin menu. -->
<div class="fluent-menu" data-role="fluentmenu">
    <ul class="tabs-holder">
        <li id="btn_tab_users" class="active">
            <a href="#tab_users">Users</a>
        </li>
        <li id="btn_tab_groups">
            <a href="#tab_groups">Groups</a>
        </li>
        <li id="btn_filter_lists">
            <a href="#tab_filter_lists">Filter Lists</a>
        </li>
        <li id="btn_user_deactivation_requests">
            <a href="#tab_user_deactivation_requests">Deactivation Requests</a>
        </li>
        <li id="btn_tab_app_groups">
            <a href="#tab_app_groups">Application Groups</a>
        </li>
        <li id="btn_app_user_activations">
            <a href="#tab_app_user_activations">App User Activations</a>
        </li>
        <li id="btn_system_versions">
            <a href="#tab_system_versions">System Versions</a>
        </li>
    </ul>

    <!-- Primary menu contents container. -->
    <div class="tabs-content">

        <!-- Users tab. -->
        <div class="tab-panel" id="tab_users" style="display: block;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_user_add" class="fluent-big-button">
                        <span class="icon mif-user-plus"></span> Create
                        <br>User
                    </button>
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_user_delete" class="fluent-big-button" disabled>
                            <span class="mif-user-minus"></span>
                            <span class="label">Delete
                                <br>User</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Creation / Deletion</div>
            </div>
        </div>

        <!-- Groups tab. -->
        <div class="tab-panel" id="tab_groups" style="display: block;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_group_add" class="fluent-big-button">
                        <span class="icon mif-users"></span> Create
                        <br>Group
                    </button>
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_group_delete" class="fluent-big-button" disabled>
                            <span class="mif-cancel"></span>
                            <span class="label">Delete
                                <br>Group</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Creation / Deletion</div>
            </div>
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_group_clone" class="fluent-big-button">
                        <span class="icon mif-users"></span> Clone
                        <br>Group
                    </button>
                </div>
                <div class="tab-group-caption">Clone Groups</div>
            </div>
        </div>

        <!-- Filter lists tab. -->
        <div class="tab-panel" id="tab_filter_lists" style="display: none;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_add_filter_lists" class="fluent-big-button">
                        <span class="icon mif-upload"></span> Upload
                        <br>Lists
                    </button>
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_delete_filter_list" class="fluent-big-button" disabled>
                            <span class="mif-cancel"></span>
                            <span class="label">Delete
                                <br/>List</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Creation / Deletion</div>
            </div>
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <!-- Default state of all delete buttons should be disabled. -->
                    <button id="btn_delete_filter_list_namespace" class="fluent-big-button" disabled>
                        <span class="mif-warning"></span>
                        <span class="label">Delete All
                            <br/>In Namespace</span>
                    </button>

                    <!-- Default state of all delete buttons should be disabled. -->
                    <button id="btn_delete_filter_list_type_namespace" class="fluent-big-button" disabled>
                        <span class="mif-warning"></span>
                        <span class="label">Delete Type
                            <br/>In Namespace</span>
                    </button>
                </div>
                <div class="tab-group-caption">!!Mass Deletion!!</div>
            </div>
        </div>

        <!-- User deactivation requests tab. -->
        <div class="tab-panel" id="tab_user_deactivation_requests" style="display: none;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_delete_user_deactivation_request" class="fluent-big-button" disabled>
                            <span class="mif-cancel"></span>
                            <span class="label">Delete
                                <br/>Request</span>
                        </button>
                        <div class="tab-content-segment">
                            <button id="btn_refresh_user_deactivation_request_list" class="fluent-big-button">
                                <span class="mif-download"></span>
                                <span class="label">Refresh</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tab-group-caption">Reply</div>
            </div>
        </div>

        <!-- App groups tab. -->
        <div class="tab-panel" id="tab_app_groups" style="display: none;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <div class="tab-content-segment app-group-content">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <label class="input-control radio">
                            <input id="global_radio_app" type="radio" name="global_app_n1" checked>
                            <span class="check"></span>
                            <span class="caption">Application</span>
                        </label>
                        <br>
                        <label class="input-control radio">
                            <input id="global_radio_app_group" type="radio" name="global_app_n1">
                            <span class="check"></span>
                            <span class="caption">Application Groups</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_application_add" class="fluent-big-button">
                        <span class="icon mif-stack"></span> Add
                        <br>Application
                    </button>
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_application_remove" class="fluent-big-button" disabled>
                            <span class="mif-cancel"></span>
                            <span class="label">Remove
                                <br>Application</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Add / Remove</div>
            </div>
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_apply_group" class="fluent-big-button">
                        <span class="icon mif-checkmark" style="color:green"></span> Apply
                        <br>To Group
                    </button>
                </div>
                <div class="tab-group-caption">Apply</div>
            </div>
        </div>

        <!-- App User Activations tab. -->
        <div class="tab-panel" id="tab_app_user_activations" style="display: none;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_delete_activation" class="fluent-big-button" disabled>
                            <span class="mif-cancel"></span>
                            <span class="label">Delete
                                <br/>Activation</span>
                        </button>
                        <div class="tab-content-segment">
                            <button id="btn_block_activations" class="fluent-big-button">
                                <span class="mif-blocked"></span>
                                <span class="label">Block
                                    <br/>Activation</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tab-group-caption">Delete/Block Activations</div>
            </div>
        </div>

        <!-- System versions tab. -->
        <div class="tab-panel" id="tab_system_versions" style="display: none;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_version_add" class="fluent-big-button">
                        <span class="icon mif-move-up"></span> Add
                        <br>Version
                    </button>
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                        <button id="btn_version_delete" class="fluent-big-button" disabled>
                            <span class="mif-move-down"></span>
                            <span class="label">Remove
                                <br>Version</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Add / Remove</div>
            </div>
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <button id="btn_sysem_platform" class="fluent-big-button">
                        <span class="icon mif-phonelink"></span> Manage
                        <br>Platforms
                    </button>
                </div>
                <div class="tab-group-caption">Platforms</div>
            </div>

        </div>
    </div>
</div>

<!-- This is the main content presentation area for the utility. Each tab's content should have its own container in here. -->
<div id="editor_container">

    <!-- User editing view. -->
    <div id="view_user_management" class="dataTables_wrapper">
        <table id="user_table" class="table striped hovered border">

        </table>
    </div>

    <!-- Group editing view. -->
    <div id="view_group_management" class="dataTables_wrapper">
        <table id="group_table" class="table striped hovered border">

        </table>
    </div>

    <!-- Filter list editing view. -->
    <div id="view_filter_management" class="dataTables_wrapper">
        <table id="filter_table" class="table striped hovered border">

        </table>
    </div>

    <!-- User requests view. -->
    <div id="view_user_deactivation_request_management" class="dataTables_wrapper">
        <table id="user_deactivation_request_table" class="table striped hovered border">

        </table>
    </div>

    <!-- AppList/AppGroupList editing view. -->
    <div id="view_app_management" class="dataTables_wrapper">
        <table id="app_table" class="table striped hovered border">

        </table>
    </div>
    <div id="view_app_group_management" class="dataTables_wrapper">
        <table id="app_group_table" class="table striped hovered border">

        </table>
    </div>

    <!-- App User Activations view. -->
    <div id="view_app_user_activations_management" class="dataTables_wrapper">
        <table id="app_user_activations_table" class="table striped hovered border">

        </table>
    </div>

    <!-- System version view. -->
    <div id="view_system_versions_management" class="dataTables_wrapper">
        <table id="system_versions_table" class="table striped hovered border">

        </table>
    </div>
</div>

<!-- Include progress-wait script/UI. -->
@include('layouts.components.progresswait')

<!-- Require user editing overlay. -->
@include('layouts.components.usereditor')

<!-- Require group editing overlay. -->
@include('layouts.components.groupeditor')

<!-- Require filter list editing overlay. -->
@include('layouts.components.filterlisteditor')

<!-- Require deactivation request editing overlay. -->
@include('layouts.components.deactivationreqeditor')

<!-- Require filter list upload overlay. -->
@include('layouts.components.listuploader')

<!-- Require applist editing overlay. -->
@include('layouts.components.appeditor')
<!-- Require appgroup list editing overlay. -->
@include('layouts.components.appgroupeditor')
<!-- Require Apply Application to App group overlay. -->
@include('layouts.components.applyapptoappgroup') @include('layouts.components.applyappgrouptousergroup')
<!-- Require Apply to group overlay. -->
@include('layouts.components.appuseractivationeditor')
<!-- Require System Version overlay. -->
@include('layouts.components.appversion')
<!-- Require Platform overlay. -->
@include('layouts.components.platform')
<!-- Include dashboard JS to drive UI. -->
<script src="{{ asset('js/admin/dashboard.js') }}">
</script>
@endsection