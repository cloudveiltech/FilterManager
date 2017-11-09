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
        <li id="btn_user_global_white_black_list"><a href="#tab_user_global_white_black_list">Global white/blacklist</a></li>
        <li id="btn_app_user_activations"><a href="#tab_app_user_activations">App User Activations</a></li>
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
                            <span class="label">Delete<br>User</span>
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
                            <span class="label">Delete<br>Group</span>
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

         <!-- Global White/Blacklist tab. -->
        <div class="tab-panel" id="tab_user_global_white_black_list" style="display: none;">
            <div class="tab-panel-group">
                <div class="tab-group-content">
                    <div class="tab-content-segment">
                        <!-- Default state of all delete buttons should be disabled. -->
                         <label class="input-control radio">
                            <input id="global_radio_blacklist" type="radio" name="global_n1" checked>
                            <span class="check"></span>
                            <span class="caption">Blacklist</span>
                        </label>
                        <br>
                        <label class="input-control radio">
                            <input id="global_radio_whitelist" type="radio" name="global_n1">
                            <span class="check"></span>
                            <span class="caption">Whitelist</span>
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
                            <span class="label">Remove<br>Application</span>
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
                            <span class="label">Delete<br/>Activations</span>
                        </button>
                        <div class="tab-content-segment">                                
                            <button id="btn_block_activations" class="fluent-big-button">
                                <span class="mif-blocked"></span>
                                <span class="label">Block<br/>Activations</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tab-group-caption">Delete/Block Activations</div>
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

    <!-- Black/Whitelist editing view. -->
    <div id="view_whitelist_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="whitelist_table" class="table striped hovered border" style="width:100%">

        </table>
    </div>
    <div id="view_blacklist_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="blacklist_table" class="table striped hovered border" style="width:100%">

        </table>
    </div>

    <!-- App User Activations view. -->
    <div id="view_app_user_activations_management" style="width: 100%; min-height: 100%; position: absolute; visibility: hidden; padding: 10px;">
        <table id="app_user_activations_table" class="table striped hovered border">

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

<!-- Require whitelist editing overlay. -->
@include('layouts.components.whitelisteditor')
<!-- Require blacklist editing overlay. -->
@include('layouts.components.blacklisteditor')
<!-- Require Apply to group overlay. -->
@include('layouts.components.applyapplication')
<!-- Require Apply to group overlay. -->
@include('layouts.components.appuseractivationeditor')
<!-- Include dashboard JS to drive UI. -->
<script src="{{ asset('js/admin/dashboard.js') }}">
</script>
@endsection
