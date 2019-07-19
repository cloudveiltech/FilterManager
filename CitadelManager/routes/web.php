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

Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::get('login/{provider}', 'Auth\LoginController@loginWithProvider')->name('login.withSso');
Route::get('login/callbacks/{provider}', 'Auth\LoginController@handleProviderCallback')->name('callback.sso');

Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function () {
    Route::get('/', function () {
        $roles = Role::all();
        return view('adminhome')->with('roles', $roles);
    });
});

Route::group(['prefix' => 'user', 'middleware' => ['role:admin|user|business-owner']], function() {
    Route::get('/', function() {
        $roles = Role::all();
        return view('userhome')->with('roles', $roles);
    });
});

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if($user->hasRole('user')) {
            return redirect('/user');
        } else {
            return redirect('/admin');
        }
    } else {
        return redirect('/login');
    }
});

Route::get('/update/{platform}/update.xml', 'UpdateController@retrieve');
Route::middleware(['auth.basic.once', 'role:admin|user'])->get('/update/{platform}', 'UpdateController@currentVersions');

Route::get('/download/latest/64', function() {
  return redirect('/releases/CloudVeil-1.6.31-winx64.msi');
});

Route::get('/download/latest/32', function() {
  return redirect('/releases/CloudVeil-1.6.31-winx86.msi');
});

Route::get('/home', 'HomeController@index')->name('home');










