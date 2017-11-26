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
Route::group(['prefix' => 'admin', 'middleware' => ['web','role:admin']], function() {

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

    Route::get('user_activations/{user_id}', 'AppUserActivationController@index');
    Route::resource('user_activations', 'AppUserActivationController');

    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');

    // Apply app blacklist or whitelist to group.
    Route::post('/applytogroup', 'ApplyToGroupController@applyToGroup');

    // For handling deletion of all records in a namespace.
    Route::delete('/filterlists/namespace/{namespace}/{type?}', 'FilterListController@deleteAllListsInNamespace');
});

// Users should only be able to pull list updates. The routes available to them
// are routes to get the sum of the current user data server side, and to request
// a download of their user data.
Route::group(['prefix' => 'user', 'middleware' => ['web','role:admin|user']], function() {

    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');    
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');
    Route::post('/me/terms', 'UserController@getUserTerms');
    
});

/**
 * Version 2 of the API.  This version relies upon basic authentication to retrieve a token and then 
 * token authentication via headers for other requests.
 */
Route::group(['prefix' => 'v2', 'middleware' => ['api','auth:api']], function() {
    
    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');    
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');
    Route::post('/me/data/getconfigonly', 'UserController@getUserDataConfigOnly');
    Route::post('/me/terms', 'UserController@getUserTerms');
    Route::post('/me/revoketoken', 'UserController@revokeUserToken');
    
    Route::get('/me/user', function (Request $request) {
        return $request->user();
    });
});

/* Administration side of v2 API. This version relies upon basic authentication to retrieve a token and then 
 * token authentication via headers for other requests.
 */
Route::group(['prefix' => 'v2/admin', 'middleware' => ['api','auth:api','role:admin']], function() {
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
    
    /* Manage Activations */
    Route::get('/activations', 'AppUserActivationController@index');
});

Route::middleware(['auth.basic.once','role:admin|user'])->post('/v2/user/gettoken', 'UserController@getUserToken'); 

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