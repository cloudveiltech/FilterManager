<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use App\Role;

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function() {
    Route::get('/', function () {
        $roles =  Role::all();
        return view('adminhome')->with('roles', $roles);
    });
});

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    } else {
        return redirect('/login');
    }
});

Route::get('/update/win{platform}/update.xml', 'UpdateController@retrieve');

Route::get('/download/latest/64', function() {
  return redirect('/releases/CloudVeil-1.3.1-x64.msi');
});

Route::get('/download/latest/32', function() {
  return redirect('/releases/CloudVeil-1.3.1-x86.msi');
});

