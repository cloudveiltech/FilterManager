@extends('layouts.appWithBootstrapAndVue') @section('navbar') @include('layouts.components.usernav') @endsection

@section('styles')
<link href="{{ asset('css/toastr.min.css') }}" rel="stylesheet" />
<link href="{{ asset('css/nouislider.min.css') }}" rel="stylesheet" />
<link href="{{ asset('css/user-dashboard-custom.css') }}" rel="stylesheet" />
@endsection

@section('scripts')
<script src="{{ asset('js/user/app.js') }}"></script>
<script src="{{ asset('js/toastr.min.js') }}"></script>
<script src="{{ asset('js/nouislider.min.js') }}"></script>
<script src="{{ asset('js/no-ui-slider.js') }}"></script>
<script src="{{ asset('js/day-restrictions.js') }}"></script>
<script src="{{ asset('js/self-moderation-entry.js') }}"></script>

<script>
    window.onUserLoad = null;

    $(document).ready(function() {
        var search = window.location.search.substring(1);
        var searchParts = search.split('&');
        var searchArray = $.map(searchParts, function(p, i) {
            return p.split('=');
        });

        var search = {};

        for(var i = 0; i < searchArray.length; i++) {
            search[searchArray[0]] = searchArray[1];
        }

        if(search.tab) {
            // Handles special case where we try to navigate to deactivation requests or activations.
            // Those tabs may not exist because the user might not be a business user.
            switch(search.tab) {
                default:
                    applyTab(search.tab);
                    break;

                case 'deactivation-requests':
                case 'activations':
                    window.onUserLoad = function(user) {
                        if(user.isBusinessOwner) {
                            applyTab(search.tab);
                        }
                    };

                    break;
            }
        }
    });

    function applyTab(tab) {
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
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a aria-controls="time-restrictions" role="tab" data-toggle="tab" href="#time-restrictions">Time Restrictions</a></li>
        <li role="presentation"><a aria-controls="self-moderation" role="tab" data-toggle="tab" href="#self-moderation">Self-moderation</a></li>
        <li role="presentation"><a aria-controls="relaxed-policy" role="tab" data-toggle="tab" href="#relaxed-policy">Relaxed Policy</a></li>
        <li v-if="isBusinessOwner" role="presentation"><a aria-controls="deactivation-requests" role="tab" data-toggle="tab" href="#deactivation-requests">Deactivation Requests</a></li>
        <li v-if="isBusinessOwner" role="presentation"><a aria-controls="activations" role="tab" data-toggle="tab" href="#activations">Activations</a></li>
    </ul>

    <div class="tab-content">
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
                <day-restrictions v-for="(day, idx) in timeRestrictions.days" :slider-config="timeRestrictions.sliderConfig" v-model="timeRestrictions.data[day]" :caption="timeRestrictions.captionDays[idx]"></day-restrictions>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" @click="timeRestrictions.save()">Save Changes</button>
                <button class="btn btn-secondary" @click="timeRestrictions.fetch()" >Cancel</button>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="self-moderation">
            <!-- TODO: Notify user that changes are saved when they finish editing -->
            
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-24">
                    <div class="row">
                        <ul class="col-xs-24 list-items self-moderation">
                            <li class="list-items-row" v-for="(item, index) in selfModeration.blacklist.data" @click="selfModeration.blacklist.editItem(index)">
                                <div class="row">
                                    <div class="site-text col-xs-20">
                                        <editable-span v-model="selfModeration.blacklist.data[index]" placeholder="(click here to edit)">
                                        </editable-span>
                                    </div>
                                    <div class="col-xs-4">
                                        <button class="btn btn-danger" @click="selfModeration.blacklist.removeUrl(item)"><span class="glyph glyph-remove"></span></button>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <button class="col-xs-24 btn btn-primary" @click="selfModeration.blacklist.addUrlEntry()"><span class="glyph glyph-add"></span> Block Site</button>
                    </div>
                </div>

                <div class="col-md-12 col-sm-12 col-xs-24">
                    <div class="row">
                        <ul class="col-xs-24 list-items self-moderation">
                            <li class="list-items-row" v-for="(item, index) in selfModeration.whitelist.data" @click="selfModeration.whitelist.editItem(index)">
                                <div class="row">
                                    <div class="site-text col-xs-20">
                                        <editable-span v-model="selfModeration.whitelist.data[index]" placeholder="(click here to edit)">
                                        </editable-span>
                                    </div>
                                    <div class="col-xs-4">
                                        <button class="btn btn-danger" @click="selfModeration.whitelist.removeUrl(item)"><span class="glyph glyph-remove"></span></button>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <button class="col-xs-24 btn btn-primary" @click="selfModeration.whitelist.addUrlEntry()"><span class="glyph glyph-add"></span> Allow Site</button>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" @click="selfModeration.save">Save Changes</button>
                <button class="btn btn-secondary" @click="selfModeration.fetch">Cancel</button>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="relaxed-policy">
            <!-- TODO: Notify user that changes are saved when they finish editing -->
            <div class="form">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" v-model="relaxedPolicy.data.enable_relaxed_policy_passcode" />
                            <span>Enable Relaxed Policy Password</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="passwordBox">Relaxed Policy Password</label>
                    <swappable-password-box v-model="relaxedPolicy.data.relaxed_policy_passcode"></swappable-password-box>
                </div>

                <div v-if="isBusinessOwner">
                    <div class="form-group">
                        <label for="edit_bypasses_permitted">Bypasses Allowed Per Day</label>
                        <input id="edit_bypasses_permitted" class="form-control" type="number" v-model="relaxedPolicy.data.bypasses_permitted"
                                placeholder="(group default)" />
                    </div>

                    <div class="form-group">
                        <label for="edit_bypass_duration">Relaxed Policy Period (minutes)</label>
                        <input id="edit_bypass_duration" class="form-control" type="number" v-model="relaxedPolicy.data.bypass_duration"
                                placeholder="(group default)" />
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
                            <button v-show="!item.granted" class="btn btn-primary" @click="deactivationRequests.grant(item)">Grant</button>
                            <button v-show="item.granted" class="btn btn-primary" disabled="disabled">Granted</button>
                        </td>
                        <td>
                            <button class="btn btn-secondary" @click="deactivationRequests.deny(item)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="isBusinessOwner" role="tabpanel" class="tab-pane" id="activations">
            <p>Use this screen to keep track of your computers that have CloudVeil for Windows products installed</p>

            <table class="table no-button-margins">
                <thead>
                    <tr>
                        <th>Computer</th>
                        <th>IP Address</th>
                        <th>Version</th>
                        <th>Updated At</th>
                        <th>...</th>
                        <th>...</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in activations.data">
                        <td>@{{item.device_id}}</td>
                        <td>@{{item.ip_address}}</td>
                        <td>@{{item.app_version}}</td>
                        <td>@{{item.updated_at}}</td>
                        <td>
                            <button class="btn btn-warning" @click="activations.blockActivation(item)">
                                <span class="glyph glyph-cancel"></span> Block
                            </button>
                        </td>
                        <td>
                            <button class="btn btn-danger" @click="activations.deleteActivation(item)">
                                <span class="glyph glyph-remove"></span> Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
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
                    <button type="button" class="btn btn-primary">
                        @{{deleteModal.confirmButtonText}}
                    </button>
                    <button type="button" class="btn btn-secondary">
                        @{{deleteModal.cancelButtonText}}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="editActivationModal"
         tabindex="-1" role="dialog"
         aria-labelledby="editActivationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="editActivationModalLabel">
                        Edit Activation
                    </h4>
                </div>
                <form class="form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="activation_device_id">Computer Name</label>
                            <input id="activation_device_id" class="form-control" type="text" v-model="activationEditor.data.device_id"
                                    readonly="readonly" />
                        </div>

                        <div class="form-group">
                            <label for="activation_identifier">Identifier</label>
                            <input id="activation_identifier" class="form-control" type="text" v-model="activationEditor.data.identifier"
                                    readonly="readonly" />
                        </div>

                        <div class="form-group">
                            <label for="activation_ip_address">IP Address</label>
                            <input id="activation_ip_address" class="form-control" type="text" v-model="activationEditor.data.ip_address"
                                    readonly="readonly" />
                        </div>

                        <div class="form-group">
                            <label for="activation_bypass_quantity">Relaxed Policy Bypasses Allowed</label>
                            <input id="activation_bypass_quantity" class="form-control" type="number" v-model="activationEditor.data.bypass_quantity" />
                        </div>

                        <div class="form-group">
                            <label for="activation_bypass_duration">Relaxed Policy Period (minutes)</label>
                            <input id="activation_bypass_duration" class="form-control" type="number" v-model="activationEditor.data.bypass_period" />
                        </div>

                        <div class="form-group">
                            <label for="activation_bypass_used">Relaxed Policy Bypasses Used</label>
                            <input id="activation_bypass_used" class="form-control" type="number" v-model="activationEditor.data.bypass_used"
                                    readonly="readonly" />
                        </div>
                    </div>
                    <div class="modal-footer">

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-sample"
         tabindex="-1" role="dialog"
         aria-labelledby="modal-sample-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-sample-label">
                        Dialog title
                    </h4>
                </div>
                <div class="modal-body">
                    Lorem ipsum dolor sit amet.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary">
                        Button
                    </button>
                    <button type="button" class="btn btn-info"
                            data-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection