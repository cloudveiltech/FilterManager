@extends('layouts.app')

@section('navbar')
@include('layouts.components.adminnav')
@endsection

@section('content')

<!-- Include BaseRecord Record Type JS. Must be done to serve any record type correctly. -->
<script src="{{ asset('js/admin/records/baserecord.js') }}">
</script>

<!-- Primary admin menu. -->
<div class="fluent-menu" data-role="fluentmenu" style="left: 0; right: 0; top: 0; margin: 80px 80px 0 80px;">
    <ul class="tabs-holder">
        <li id="btn_tab_users" class="active"><a href="#tab_users">Users</a></li>
        <li id="btn_tab_groups"><a href="#tab_groups">Groups</a></li>
        <li id="btn_filter_lists"><a href="#tab_filter_lists">Filter Lists</a></li>
        <li id="btn_user_deactivation_requests"><a href="#tab_user_deactivation_requests">Deactivation Requests</a></li>
    </ul>

    <!-- Primary menu contents container. -->
    <div class="tabs-content">

        <!-- Users tab. -->
        <div class="tab-panel" id="tab_users" style="display: flex;">
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
                            <span class="label">Delete<br>User</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Creation / Deletion</div>
            </div>      
            <div class="extra-userdetail">
                <table calss="table table-striped" id="detail-table">
                    <thead>
                        <tr>
                            <th>UserId</th>
                            <th>Identifier</th>
                            <th>DeviceId</th>
                            <th>Date Created</th>
                            <th>Date Updated</th>
                            <th>IPaddress</th>
                        </tr>
                    </thead>
                </table>
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
                            <span class="label">Delete<br>Group</span>
                        </button>
                    </div>
                </div>
                <div class="tab-group-caption">Creation / Deletion</div>
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
                            <span class="label">Delete<br/>List</span>
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
                        <span class="label">Delete All<br/>In Namespace</span>
                    </button>
                    
                    <!-- Default state of all delete buttons should be disabled. -->
                    <button id="btn_delete_filter_list_type_namespace" class="fluent-big-button" disabled>
                        <span class="mif-warning"></span>
                        <span class="label">Delete Type<br/>In Namespace</span>
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
                            <span class="label">Delete<br/>Request</span>
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
    </div>
</div>

<!-- This is the main content presentation area for the utility. Each tab's content should have its own container in here. -->
<div id="editor_container" style="margin: 250px 80px 80px 80px; position:absolute; top:0; left:0; right:0; bottom:0; background: white; overflow-y: scroll;">

    <!-- User editing view. -->
    <div id="view_user_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="user_table" class="table striped hovered border" style="width:100%">
            
        </table>
    </div>        

    <!-- Group editing view. -->
    <div id="view_group_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="group_table" class="table striped hovered border" style="width:100%">

        </table>
    </div>

    <!-- Filter list editing view. -->
    <div id="view_filter_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="filter_table" class="table striped hovered border" style="width:100%">

        </table>
    </div>

    <!-- User requests view. -->
    <div id="view_user_deactivation_request_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="user_deactivation_request_table" class="table striped hovered border">

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

<!-- Include dashboard JS to drive UI. -->
<script src="{{ asset('js/admin/dashboard.js') }}">
</script>
@endsection
