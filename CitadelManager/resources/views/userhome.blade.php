@extends('layouts.appWithBootstrap') @section('navbar') @include('layouts.components.usernav') @endsection

@section('styles')
<link href="{{ asset('css/user-dashboard-custom.css') }}" rel="stylesheet" />
@endsection

@section('content')

<div class="container">
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a aria-controls="time-restrictions" role="tab" data-toggle="tab" href="#time-restrictions">Time Restrictions</a></li>
    <li role="presentation"><a aria-controls="self-moderation" role="tab" data-toggle="tab" href="#self-moderation">Self-moderation</a></li>
    <li role="presentation"><a aria-controls="relaxed-policy" role="tab" data-toggle="tab" href="#relaxed-policy">Relaxed Policy</a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="time-restrictions">
        <!-- TODO: Notify user that changes are saved when they finish editing -->
        <!-- Last saved: blah-blah-blah --> <!-- Changes saved! -->

        <div class="time-restrictions entity-list entity-list-add-item-button">
            <a class="entity-list-item">
                <div class="item-icon">
                    <span class="glyph glyph-add"></span>
                </div>
                <div class="item-content-primary">
                    <div class="content-text-primary">Add Time Restrictions</div>
                </div>
            </a>

            <div class="entity-list-item time-block">
                <div class="item-content-secondary">
                    <button class="btn btn-danger">-</button>
                </div>
                <div class="item-content-primary">
                    <div class="form">
                        <div class="form-group timepickers">
                            <label>Time to block</label>
                            <input type="text" class="form-control timepicker" />
                            <span>to</span>
                            <input type="text" class="form-control timepicker" />
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" />
                                <span>Monday</span>
                            </label>
                            <label>
                                <input type="checkbox" />
                                <span>Tuesday</span>
                            </label>
                            <label>
                                <input type="checkbox" />
                                <span>Wednesday</span>
                            </label>
                            <label>
                                <input type="checkbox" />
                                <span>Thursday</span>
                            </label>
                            <label>
                                <input type="checkbox" />
                                <span>Friday</span>
                            </label>
                            <label>
                                <input type="checkbox" />
                                <span>Saturday</span>
                            </label>
                            <label>
                                <input type="checkbox" />
                                <span>Sunday</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div role="tabpanel" class="tab-pane" id="self-moderation">
        <!-- TODO: Notify user that changes are saved when they finish editing -->
        
        <div class="row">
            <ul class="list-items self-moderation col-md-9 col-sm-12 col-xs-24">
                <li class="list-items-row">
                    <div class="row">
                        <div class="site-text col-xs-20">
                            bbc.com
                        </div>
                        <div class="col-xs-4">
                            <button class="btn btn-danger"><span class="glyph glyph-remove"></span></button>
                        </div>
                    </div>
                </li>
                <li class="list-items-row">
                    <div class="row">
                        <div class="site-text col-xs-20">
                            bbc.com
                        </div>
                        <div class="col-xs-4">
                            <button class="btn btn-danger"><span class="glyph glyph-remove"></span></button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <button class="btn btn-primary"><span class="glyph glyph-add"></span> Add Site or URL</button>
    </div>

    <div role="tabpanel" class="tab-pane" id="relaxed-policy">
        <!-- TODO: Notify user that changes are saved when they finish editing -->
        <div class="form">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" />
                        <span>Enable Relaxed Policy Password</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="passwordBox">Relaxed Policy Password</label>
                <input type="text" class="form-control" />
            </div>
        </div>
    </div>
</div>

</div>

@endsection