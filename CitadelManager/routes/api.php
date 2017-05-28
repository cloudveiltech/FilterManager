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

// Almost everything to do with this system should be restricted to admin
// role people only. In the future, more roles should be provided.
// For example, there should be group admins, where a user is given
// access/responsibility to manage their group. This would need to
// be factored into some design changes here.
Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function() {

    Route::resource('users', 'UserController');
    Route::resource('groups', 'GroupController');
    Route::resource('deactivationreq', 'DeactivationRequestController');
    Route::resource('filterlists', 'FilterListController');
    Route::resource('blockreview', 'BlockActionReviewRequestController');
    
    // For handling mass upload of filter lists.
    Route::post('/filterlists/upload', 'FilterListController@processUploadedFilterLists');
    
    // For handling deletion of all records in a namespace.
    Route::delete('/filterlists/namespace/{namespace}/{type?}', 'FilterListController@deleteAllListsInNamespace');
});

// Users should only be able to pull list updates. The routes available to them
// are routes to get the sum of the current user data server side, and to request
// a download of their user data.
Route::group(['prefix' => 'user', 'middleware' => ['role:admin|user']], function() {

    Route::post('/me/deactivate', 'UserController@getCanUserDeactivate');    
    Route::post('/me/data/check', 'UserController@checkUserData');
    Route::post('/me/data/get', 'UserController@getUserData');
    Route::post('/me/terms', 'UserController@getUserTerms');
    
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


