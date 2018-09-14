<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

/*
 * --------------------------------------------------------
 * Important Change
 * --------------------------------------------------------
 * The 'api' middleware group has been removed from this file in RouteServiceProvider.php
 * to allow us to use the 'web' middleware group on those items in this file which
 * authenticate via that middleware.  Because of this you must add the api middleware to
 * routes which should be protected by it.
 */

// Almost everything to do with this system should be restricted to admin
// role people only. In the future, more roles should be provided.
// For example, there should be group admins, where a user is given
// access/responsibility to manage their group. This would need to
// be factored into some design changes here.

/*
 * ATTENTION: We are looking at migrating this section of routes to be accessible with api protection
 * These routes are all duplicated farther down in the file.
 * Any additional routes should go in v2/admin to allow us to keep integrating with other systems.
 */
Route::group(['prefix' => 'admin', 'middleware' => ['db.live', 'web', 'role:admin']], function () {

    Route::resource('users', 'UserController');
    Route::post('users/update_field', 'UserController@updateField');

    Route::resource('groups', 'GroupController');
    Route::post('groups/update_field', 'GroupController@updateField');
    Route::get('group/all', 'GroupController@get_groups');

    Route::resource('deactivationreq', 'DeactivationRequestController');
    Route::post('deactivationreq/update_field', 'DeactivationRequestController@updateField');

    Route::resource('filterlists', 'FilterListController');
    Route::get('filterlist/all', 'FilterListController@get_filters');

    Route::resource('blockreview', 'BlockActionReviewRequestController');

    //Route::get('activations/{id}', 'UserController@activation_data');
    Route::resource('user_activations', 'AppUserActivationController');
    Route::post('user_activations/delete/{id}', 'AppUserActivationController@destroy');
    Route::post('user_activations/block/{id}', 'AppUserActivationController@block');
    Route::get('user_activations/{user_id}', 'AppUserActivationController@index');

    Route::get('activations', 'AppUserActivationController@index');
    Route::post('activations/update_report', 'AppUserActivationController@updateReport');
    Route::post('activations/update_alert', 'AppUserActivationController@updateAlert');
    Route::post('activations/update_check_in_days', 'AppUserActivationController@updateCheckInDays');

    Route::resource('whitelists', 'GlobalWhitelistController');
    Route::resource('blacklists', 'GlobalBlacklistController');

    Route::resource('app', 'ApplicationController');
    Route::resource('app_group', 'ApplicationGroupController');

    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');

    // Apply app  to appgroup.
    Route::post('/apply_app_to_app_group', 'ApplyAppToAppGroupController@applyToGroup');
    Route::get('/apply_app_to_appgroup/data', 'ApplyAppToAppGroupController@getRetrieveData');
    Route::get('/apply_app_to_appgroup/selected_group/{id}', 'ApplyAppToAppGroupController@getSelectedGroups');

    // Apply app  to appgroup.
    Route::post('/apply_appgroup_to_usergroup', 'ApplyAppgroupToUsergroupController@applyToGroup');
    Route::get('/apply_appgroup_to_usergroup/data', 'ApplyAppgroupToUsergroupController@getRetrieveData');
    Route::get('/apply_appgroup_to_usergroup/selected_user_group/{id}', 'ApplyAppgroupToUsergroupController@getSelectedUsergroups');

    // For handling deletion of all records in a namespace.
    Route::delete('/filterlists/namespace/{namespace}/{type?}', 'FilterListController@deleteAllListsInNamespace');

    // Get application for app_group_editing.
    Route::get('/applications', 'ApplicationController@get_application');
    Route::get('/get_app_data', 'GroupController@get_app_data');
    Route::get('/get_app_data/{id}', 'GroupController@get_app_data_with_groupid');
    //Route::get('/get_current_applications', 'ApplicationController@getApps');

    Route::get('/get_appgroup_data', 'ApplicationController@get_appgroup_data');
    Route::get('/get_appgroup_data/{id}', 'ApplicationController@get_appgroup_data_with_app_id');

    Route::get('/versions', 'SystemVersionController@index');
    Route::post('/versions/update_status', 'SystemVersionController@updateStatus');
    Route::get('/platforms', 'SystemVersionController@getPlatforms');
    Route::post('/platform/create', 'SystemVersionController@createPlatform');
    Route::post('/platform/update/{id}', 'SystemVersionController@updatePlatform');
    Route::post('/platform/delete', 'SystemVersionController@deletePlatform');
    Route::get('/extensions', 'SystemVersionController@getExtensions');
    Route::get('/extensions/selected/{os}', 'SystemVersionController@getOsExtension');
    Route::post('/extensions/update', 'SystemVersionController@updateExtension');
    Route::resource('version', 'SystemVersionController');

});

// Users should only be able to pull list updates. The routes available to them
// are routes to get the sum of the current user data server side, and to request
// a download of their user data.
Route::group(['prefix' => 'user', 'middleware' => ['db.live', 'web', 'role:admin|user']], function () {

    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');
    Route::post('/me/terms', 'UserController@getUserTerms');
    Route::get('/time', 'UserController@getTime');

});

/**
 * Version 2 of the API.  This version relies upon basic authentication to retrieve a token and then
 * token authentication via headers for other requests.
 */
Route::group(['prefix' => 'v2', 'middleware' => ['db.live', 'api', 'auth:api']], function () {

    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');

    Route::post('/me/terms', 'UserController@getUserTerms');
    Route::post('/me/revoketoken', 'UserController@revokeUserToken');
    Route::post('/me/bypass', 'AppUserActivationController@bypass');
    Route::post('/me/accountability', 'AccountabilityController@index');

    // START - New Requests for 1.7.
    Route::post('/me/config/get','UserController@getConfig'); //This will get the configuration JSON file for the currently authenticated user.
    Route::post('/me/config/check', 'UserController@checkConfig'); //This will return a checksum for the above-mentioned configuration file.
    Route::post('/rules/get', 'UserController@getRules'); //This will get a ZIP file of all rules available in the system.
    Route::post('/rules/check', 'UserController@checkRules'); //This will return a checksum for the above-mentioned ZIP file.
    Route::post('/rules/rebuild', 'UserController@rebuildRules');
    // END - New Requests for 1.7

    Route::get('/time', 'UserController@getTime');
    Route::get('/me/user', function (Request $request) {
        return $request->user();
    });
});

/* Administration side of v2 API. This version relies upon basic authentication to retrieve a token and then
 * token authentication via headers for other requests.
 *
 * retrievetoken is used for installs where the client side has lost the token due to uninstalls but the user
 * or activation hasn't been removed from the web service.  In that case we accept the system "identifier" and
 * generate a new token.  We return that token and email address so the app can login again.
 */
Route::group(['prefix' => 'v2/admin', 'middleware' => ['db.live', 'api', 'auth:api', 'role:admin']], function () {
    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');

    /* Manage Users */
    Route::put('/users/{id}', 'UserController@update');
    Route::get('/users', 'UserController@index');
    Route::post('/user', 'UserController@store');

    /* Return Current User */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/activations', 'AppUserActivationController@index');

    /*Start API Auth Routes*/
    Route::resource('users', 'UserController');
    Route::post('users/update_field', 'UserController@updateField');

    Route::resource('groups', 'GroupController');
    Route::post('groups/update_field', 'GroupController@updateField');
    Route::get('group/all', 'GroupController@get_groups');

    Route::resource('deactivationreq', 'DeactivationRequestController');
    Route::post('deactivationreq/update_field', 'DeactivationRequestController@updateField');

    Route::resource('filterlists', 'FilterListController');
    Route::get('filterlist/all', 'FilterListController@get_filters');

    Route::resource('blockreview', 'BlockActionReviewRequestController');

    //Route::get('activations/{id}', 'UserController@activation_data');
    Route::post('user_activations/delete/{id}', 'AppUserActivationController@destroy');
    Route::post('user_activations/block/{id}', 'AppUserActivationController@block');

    Route::get('activations', 'AppUserActivationController@index');
    Route::post('activations/update_report', 'AppUserActivationController@updateReport');
    Route::post('activations/update_alert', 'AppUserActivationController@updateAlert');
    Route::post('activations/update_check_in_days', 'AppUserActivationController@updateCheckInDays');

    Route::resource('whitelists', 'GlobalWhitelistController');
    Route::resource('blacklists', 'GlobalBlacklistController');
    Route::resource('app', 'ApplicationController');
    Route::resource('app_group', 'ApplicationGroupController');
    Route::get('user_activations/{user_id}', 'AppUserActivationController@index');
    Route::resource('user_activations', 'AppUserActivationController');

    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');

    // Apply app  to appgroup.
    Route::post('/apply_app_to_app_group', 'ApplyAppToAppGroupController@applyToGroup');
    Route::get('/apply_app_to_appgroup/data', 'ApplyAppToAppGroupController@getRetrieveData');
    Route::get('/apply_app_to_appgroup/selected_group/{id}', 'ApplyAppToAppGroupController@getSelectedGroups');

    // Apply app  to appgroup.
    Route::post('/apply_appgroup_to_usergroup', 'ApplyAppgroupToUsergroupController@applyToGroup');
    Route::get('/apply_appgroup_to_usergroup/data', 'ApplyAppgroupToUsergroupController@getRetrieveData');
    Route::get('/apply_appgroup_to_usergroup/selected_user_group/{id}', 'ApplyAppgroupToUsergroupController@getSelectedUsergroups');

    // For handling deletion of all records in a namespace.
    Route::delete('/filterlists/namespace/{namespace}/{type?}', 'FilterListController@deleteAllListsInNamespace');

    // Get application for app_group_editing.
    Route::get('/applications', 'ApplicationController@get_application');
    Route::get('/get_app_data', 'GroupController@get_app_data');
    Route::get('/get_app_data/{id}', 'GroupController@get_app_data_with_groupid');
    //Route::get('/get_current_applications', 'ApplicationController@getApps');

    Route::get('/get_appgroup_data', 'ApplicationController@get_appgroup_data');
    Route::get('/get_appgroup_data/{id}', 'ApplicationController@get_appgroup_data_with_app_id');

    Route::get('/versions', 'SystemVersionController@index');
    Route::post('/versions/update_status', 'SystemVersionController@updateStatus');
    Route::get('/platforms', 'SystemVersionController@getPlatforms');
    Route::post('/platform/create', 'SystemVersionController@createPlatform');
    Route::post('/platform/update/{id}', 'SystemVersionController@updatePlatform');
    Route::post('/platform/delete', 'SystemVersionController@deletePlatform');
    Route::resource('version', 'SystemVersionController');
    /*End API Auth Routes*/
});

/* Token Management */
Route::middleware(['auth.basic.once', 'role:admin|user'])->post('/v2/user/gettoken', 'UserController@getUserToken');
Route::post('/v2/user/retrievetoken', 'UserController@retrieveUserToken');

/**
 * Management section of the API.  This is used for working with users from external sources and relies upon basic auth.
 * At some point this will be revoked and rolled into v2 of the api.
 */
Route::group(['prefix' => 'manage', 'middleware' => ['db.live', 'auth.basic.once', 'role:admin']], function () {
    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');

    /* Manage Users */
    Route::post('/users/{id}', 'UserController@update'); //Should be deprecated.
    Route::post('/user/{id}', 'UserController@update');
    Route::get('/users', 'UserController@index'); //Should be deprecated.
    Route::get('/user', 'UserController@index');
    Route::post('/user', 'UserController@store');

    /* Return Current User */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /* Manage Activations */
    Route::get('/activations', 'AppUserActivationController@index'); //Should be deprecated.
    Route::get('/activation', 'AppUserActivationController@index');
    Route::get('/activation/status/{identify}', 'AppUserActivationController@status');
    Route::get('/deactivation/{id}', 'DeactivationRequestController@update');

});

Route::group(['middleware' => []], function () {
    Route::post('upload/log', 'UserController@uploadLog');
});
