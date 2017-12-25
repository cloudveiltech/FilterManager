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
Route::group(['prefix' => 'admin', 'middleware' => ['db.live','web','role:admin']], function() {

    Route::resource('users', 'UserController');
    Route::resource('groups', 'GroupController');
    Route::resource('deactivationreq', 'DeactivationRequestController');
    Route::resource('filterlists', 'FilterListController');
    Route::resource('blockreview', 'BlockActionReviewRequestController');

    //Route::get('activations/{id}', 'UserController@activation_data');
    //Route::post('user_activations/delete/{id}', 'AppUserActivationController@destroy');
    Route::post('user_activations/block/{id}', 'AppUserActivationController@block');
    Route::get('activations', 'AppUserActivationController@index');

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
    
    
});

// Users should only be able to pull list updates. The routes available to them
// are routes to get the sum of the current user data server side, and to request
// a download of their user data.
Route::group(['prefix' => 'user', 'middleware' => ['db.live','web','role:admin|user']], function() {

    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');    
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');
    Route::post('/me/terms', 'UserController@getUserTerms');
    
});

/**
 * Version 2 of the API.  This version relies upon basic authentication to retrieve a token and then 
 * token authentication via headers for other requests.
 */
Route::group(['prefix' => 'v2', 'middleware' => ['db.live','api','auth:api']], function() {
    
    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');    
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');
    Route::post('/me/data/getconfigonly', 'UserController@getUserDataConfigOnly');
    Route::post('/me/terms', 'UserController@getUserTerms');
    Route::post('/me/revoketoken', 'UserController@revokeUserToken');
    Route::post('/me/bypass', 'AppUserActivationController@bypass');
    
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
Route::group(['prefix' => 'v2/admin', 'middleware' => ['db.live','api','auth:api','role:admin']], function() {
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
});

/* Token Management */
Route::middleware(['auth.basic.once','role:admin|user'])->post('/v2/user/gettoken', 'UserController@getUserToken'); 
Route::post('/v2/user/retrievetoken', 'UserController@retrieveUserToken');


/**
 * Management section of the API.  This is used for working with users from external sources and relies upon basic auth.
 * At some point this will be revoked and rolled into v2 of the api.
 */
Route::group(['prefix' => 'manage', 'middleware' => ['db.live','auth.basic.once','role:admin']], function() {
    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');

    /* Manage Users */
    Route::post('/users/{id}', 'UserController@update');
    Route::get('/users', 'UserController@index');
    Route::post('/user', 'UserController@store');

    /* Return Current User */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /* Manage Activations */
    Route::get('/activations', 'AppUserActivationController@index');
    Route::get('/activation/status/{identify}', 'AppUserActivationController@status');
    
});

Route::group(['middleware' => []], function() {
    Route::post('upload/log', 'UserController@uploadLog');
});
