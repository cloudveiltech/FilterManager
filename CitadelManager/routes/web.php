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

Auth::routes();

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

/*
Route::get('/', function () {    
                // check the current user
                if (!Entrust::hasRole('admin')) {                    
                    //App::abort(403);
                    //return redirect('/test');
                }
                
                return view('adminhome');
            });
            
Route::get('/test', function () {
    return view('test');
});
 */



//Route::get('/home', 'HomeController@index')->name('home');
