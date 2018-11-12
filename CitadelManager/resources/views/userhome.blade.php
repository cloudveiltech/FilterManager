@extends('layouts.appWithBootstrap') @section('navbar') @include('layouts.components.usernav') @endsection

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
@endsection

@section('content')

<div class="container" id="app">
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a aria-controls="time-restrictions" role="tab" data-toggle="tab" href="#time-restrictions">Time Restrictions</a></li>
        <li role="presentation"><a aria-controls="self-moderation" role="tab" data-toggle="tab" href="#self-moderation">Self-moderation</a></li>
        <li role="presentation"><a aria-controls="relaxed-policy" role="tab" data-toggle="tab" href="#relaxed-policy">Relaxed Policy</a></li>
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
                <ul class="list-items self-moderation col-md-9 col-sm-12 col-xs-24">
                    <li class="list-items-row" v-for="(item, index) in selfModeration.data" @click="selfModeration.editItem(index)">
                        <div class="row">
                            <div class="site-text col-xs-20">
                                <editable-span v-model="selfModeration.data[index]" placeholder="(click here to edit)">
                                </editable-span>
                            </div>
                            <div class="col-xs-4">
                                <button class="btn btn-danger" @click="selfModeration.removeUrl(item)"><span class="glyph glyph-remove"></span></button>
                            </div>
                        </div>
                    </li>
                </ul>

            </div>

            <div class="row">
                <div class="col-md-9 col-sm-12 col-xs-24"><button class="btn btn-primary" @click="selfModeration.addUrlEntry()"><span class="glyph glyph-add"></span> Add Site or URL</button></div>
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

                <div class="btn-group">
                    <button class="btn btn-primary" @click="relaxedPolicy.save">Save Changes</button>
                    <button class="btn btn-secondary" @click="relaxedPolicy.fetch">Cancel</button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection