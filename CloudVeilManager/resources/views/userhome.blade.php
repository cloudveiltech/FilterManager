@extends('layouts.appWithBootstrapAndVue') @section('navbar') @include('layouts.components.usernav') @endsection

@section('styles')
    <link href="{{ asset('css/toastr.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/nouislider.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/user-dashboard-custom.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/citadel-main.css') }}" rel="stylesheet"/>
    <link href="{{ asset('fontawesome/css/all.css') }}" rel="stylesheet"/>
@endsection

@section('scripts')
    <script src="{{ asset('js/user/app.js') }}"></script>
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/nouislider.min.js') }}"></script>
    <script src="{{ asset('js/no-ui-slider.js') }}"></script>
    <script src="{{ asset('js/day-restrictions.js') }}"></script>
    <script src="{{ asset('js/self-moderation-entry.js') }}"></script>

    <script>
        var ACTIVATIONS_ALL_ID = "{{ \App\Http\Controllers\UserController::ID_ACTIVATION_ALL }}";
        window.onUserLoad = null;
        window.pathPartsLeft = null;

        function queryParameters() {
            var search = window.location.search.substring(1);
            var searchParts = search.split('&');
            var searchArray = $.map(searchParts, function (p, i) {
                var arr = p.split('=');
                if (arr.length < 2) {
                    arr.push(null);
                } else {
                    arr[1] = decodeURIComponent(arr[1]);
                }

                return arr;
            });

            var search = {};

            for (var i = 0; i < searchArray.length; i++) {
                search[searchArray[0]] = searchArray[1];
            }

            return search;
        }

        $(document).ready(function () {
            var search = queryParameters();

            if (search.path) {
                var pathParts = search.path.split('/');

                search.tab = pathParts[0] || "time-restrictions";
                window.pathPartsLeft = pathParts.slice(1);
            }

            if (search.tab) {
                // Handles special case where we try to navigate to deactivation requests or activations.
                // Those tabs may not exist because the user might not be a business user.
                switch (search.tab) {
                    default:
                        applyTab(search.tab, search);
                        break;

                    case 'deactivation-requests':
                        window.onUserLoad = function (user) {
                            if (user.isBusinessOwner) {
                                applyTab(search.tab, search);
                            }
                        };

                        break;
                }
            }
        });

        function applyTab(tab, search) {
            var tabList = $("#app [role='tablist']");
            var tabContent = $("#app .tab-content");

            tabList.find("li").removeClass("active");
            tabContent.find("[role='tabpanel']").removeClass("active");

            var a = tabList.find("li a[href='#" + tab + "']");
            a.parent().addClass("active");

            var pane = tabContent.find("#" + tab);
            pane.addClass("active");

        }
    </script>
@endsection

@section('content')

    <div class="container" id="app">
        <div class="topnav-container">
            <ul class="nav nav-tabs theme-alt">
                <li role="presentation" class="active">
                    <a aria-controls="time-restrictions" role="tab" data-toggle="tab" href="#time-restrictions">
                        <i class="fas fa-lock"></i>
                    </a>
                </li>
                <li role="presentation">
                    <a aria-controls="self-moderation" role="tab" data-toggle="tab" href="#self-moderation">
                        <i class="fas fa-ban"></i>
                    </a>
                </li>

                <li role="presentation">
                    <a aria-controls="relaxed-policy" role="tab" data-toggle="tab" href="#relaxed-policy">
                        <i class="fas fa-eye"></i>
                    </a>
                </li>

                <li v-if="isBusinessOwner" role="presentation">
                    <a aria-controls="deactivation-requests" role="tab" data-toggle="tab" href="#deactivation-requests">
                    <span class="fa-stack custom">
                        <i class="fas fa-ban fa-stack-1x"></i>
                        <i class="fas fa-desktop fa-stack-1x smaller-0_5x"></i>
                    </span>
                    </a>
                </li>

                <li role="presentation">
                    <a aria-controls="activations" role="tab" data-toggle="tab" href="#activations">
                        <i class="fas fa-desktop"></i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="sidenav-container">
                <ul class="nav side-navigation side-navigation-large nav-tabs theme-alt custom-nav-tabs"
                    role="tablist">
                    <li role="presentation" class="active">
                        <a aria-controls="time-restrictions" role="tab" data-toggle="tab" href="#time-restrictions">
                            <i class="fas fa-lock"></i> Time Restrictions
                        </a>
                    </li>
                    <li role="presentation">
                        <a aria-controls="self-moderation" role="tab" data-toggle="tab" href="#self-moderation">
                            <i class="fas fa-ban"></i> Self-moderation
                        </a>
                    </li>

                    <li role="presentation">
                        <a aria-controls="relaxed-policy" role="tab" data-toggle="tab" href="#relaxed-policy">
                            <i class="fas fa-eye"></i> Relaxed Policy
                        </a>
                    </li>

                    <li v-if="isBusinessOwner" role="presentation">
                        <a aria-controls="deactivation-requests" role="tab" data-toggle="tab"
                           href="#deactivation-requests">
                        <span class="fa-stack custom">
                            <i class="fas fa-ban fa-stack-1x"></i>
                            <i class="fas fa-desktop fa-stack-1x smaller-0_5x"></i>
                        </span> Deactivation Requests
                        </a>
                    </li>

                    <li role="presentation">
                        <a aria-controls="activations" role="tab" data-toggle="tab" href="#activations">
                            <i class="fas fa-desktop"></i> Activations
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-l-sm p-r-sm">
                <div role="tabpanel" class="tab-pane active" id="time-restrictions">
                    <!-- TODO: Notify user that changes are saved when they finish editing -->
                    <!-- Last saved: blah-blah-blah --> <!-- Changes saved! -->

                    <div class="presets btn-group horizontal">
                        <label>Presets</label>

                        <button @click="timeRestrictions.presets.evening()" class="btn btn-secondary">Evening</button>
                        <button @click="timeRestrictions.presets.office()" class="btn btn-secondary">Office</button>
                        <button @click="timeRestrictions.presets.none()" class="btn btn-secondary">None</button>
                    </div>

                    <div class="form">
                        <day-restrictions v-for="(day, idx) in timeRestrictions.days"
                                          :slider-config="timeRestrictions.sliderConfig"
                                          v-model="timeRestrictions.data[day]"
                                          :caption="timeRestrictions.captionDays[idx]"></day-restrictions>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-primary" @click="timeRestrictions.save()">Save Changes</button>
                        <button class="btn btn-secondary" @click="timeRestrictions.fetch()">Cancel</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="self-moderation">
                    <!-- TODO: Notify user that changes are saved when they finish editing -->

                    <div class="row">
                        <div class="col-md-8 col-sm-8 col-xs-24 self-moderation-container">
                            <self-moderation-list
                                    isurl="true"
                                    :activations="activations.data"
                                    v-model="selfModeration.blacklist"
                                    add-button-text="Block Site"></self-moderation-list>
                        </div>

                        <div class="col-md-8 col-sm-8 col-xs-24 self-moderation-container">
                            <self-moderation-list
                                    :activations="activations.data"
                                    v-model="selfModeration.triggerBlacklist"
                                    add-button-text="Block Text Trigger"></self-moderation-list>
                        </div>

                        <div v-show="isBusinessOwner" class="col-md-8 col-sm-8 col-xs-24 self-moderation-container">
                            <self-moderation-list
                                    isurl="true"
                                    :activations="activations.data"
                                    v-model="selfModeration.whitelist"
                                    add-button-text="Allow Site"></self-moderation-list>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-primary" @click="selfModeration.save">Save Changes</button>
                        <button class="btn btn-secondary" @click="selfModeration.fetch">Cancel</button>
                    </div>

                    <p>
                        NOTE: You can modify a specific computer's self-moderation lists by going to the Activations tab
                        and editing the activation there.
                    </p>
                </div>

                <div role="tabpanel" class="tab-pane" id="relaxed-policy">
                    <!-- TODO: Notify user that changes are saved when they finish editing -->
                    <div class="form">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" v-model="relaxedPolicy.data.enable_relaxed_policy_passcode"/>
                                    <span>Enable Relaxed Policy Password</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="passwordBox">Relaxed Policy Password</label>
                            <swappable-password-box
                                    v-model="relaxedPolicy.data.relaxed_policy_passcode"></swappable-password-box>
                        </div>

                        <div v-if="isBusinessOwner">
                            <div class="form-group">
                                <label for="edit_bypasses_permitted">Bypasses Allowed Per Day</label>
                                <input id="edit_bypasses_permitted" class="form-control" type="number"
                                       v-model="relaxedPolicy.data.bypasses_permitted"
                                       placeholder="(group default)"/>
                            </div>

                            <div class="form-group">
                                <label for="edit_bypass_duration">Relaxed Policy Period (minutes)</label>
                                <input id="edit_bypass_duration" class="form-control" type="number"
                                       v-model="relaxedPolicy.data.bypass_duration"
                                       placeholder="(group default)"/>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button class="btn btn-primary" @click="relaxedPolicy.save">Save Changes</button>
                            <button class="btn btn-secondary" @click="relaxedPolicy.fetch">Cancel</button>
                        </div>
                    </div>
                </div>

                <div v-if="isBusinessOwner" role="tabpanel" class="tab-pane" id="deactivation-requests">
                    <p>Use this screen to process deactivation requests from your computers</p>
                    <button class="btn btn-primary" @click="deactivationRequests.fetch()"><span
                                class="glyph glyph-refresh"></span> Refresh
                    </button>
                    <table class="table no-button-margins">
                        <thead>
                        <tr>
                            <th>Computer</th>
                            <th>...</th>
                            <th>...</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(item, index) in deactivationRequests.data">
                            <td>@{{item.device_id}}</td>
                            <td>
                                <button v-show="!item.granted" class="btn btn-primary"
                                        @click="deactivationRequests.grant(item)">Grant
                                </button>
                                <button v-show="item.granted" class="btn btn-primary" disabled="disabled">Granted
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-secondary" @click="deactivationRequests.deny(item)">Delete
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div role="tabpanel" class="tab-pane" id="activations">
                    <p>Use this screen to keep track of your computers that have CloudVeil for Windows products
                        installed</p>

                    <table class="table no-button-margins">
                        <thead>
                        <tr>
                            <th>Computer</th>
                            <th>IP Address</th>
                            <th>Friendly Name</th>
                            <th>Version</th>
                            <td>Os Version</td>
                            <th>Updated At</th>
                            <th>...</th>
                            <th v-if="isBusinessOwner">...</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(item, index) in activations.data">
                            <td>@{{item.device_id}}</td>
                            <td>@{{item.ip_address}}</td>
                            <td>@{{item.friendly_name}}</td>
                            <td>@{{item.app_version}}</td>
                            <td>@{{item.os_version}}</td>
                            <td>@{{item.updated_at}}</td>
                            <td>
                                <button class="btn btn-primary" @click="activations.editActivation(item)">
                                    <span class="glyph glyph-edit"></span> Edit
                                </button>
                            </td>
                            <td v-if="isBusinessOwner">
                                <button class="btn btn-warning" @click="activations.blockActivation(item)">
                                    <span class="glyph glyph-cancel"></span> Revoke Activation
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal" id="deleteModal"
             tabindex="-1" role="dialog"
             aria-labelledby="modalDeleteLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalDeleteLabel">
                            @{{deleteModal.title}}
                        </h4>
                    </div>
                    <div class="modal-body">
                        @{{deleteModal.body}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="deleteModal.onConfirm()">
                            @{{deleteModal.confirmButtonText}}
                        </button>
                        <button type="button" class="btn btn-secondary" @click="deleteModal.close()">
                            @{{deleteModal.cancelButtonText}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.components.userhome.activationeditormodal')

    </div>

@endsection
