<?php


// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

use App\Http\Middleware\CheckIfAdmin;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin'),
        [CheckIfAdmin::class],
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('filter-list', 'FilterListCrudController');
    Route::crud('user', 'UserCrudController');
    Route::crud('group', 'GroupCrudController');
    Route::crud('deactivation-request', 'DeactivationRequestCrudController');
    Route::crud('app-group', 'AppGroupCrudController');
    Route::crud('app-user-activation', 'AppUserActivationCrudController');
    Route::crud('system-version', 'SystemVersionCrudController');
    Route::crud('system-platform', 'SystemPlatformController');
    Route::crud('app', 'AppCrudController');
    Route::post('api/filter_list', 'GroupCrudController@loadFilterLists');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
