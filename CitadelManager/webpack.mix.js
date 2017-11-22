const { mix } = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/assets/js/progresswait.js', 'public/js/progresswait.js')
    .js('resources/assets/js/admin/applytogroupoverlay.js', 'public/js/admin/applytogroupoverlay.js')
    .js('resources/assets/js/admin/dashboard.js', 'public/js/admin/dashboard.js')
    .js('resources/assets/js/admin/listuploadoverlay.js', 'public/js/admin/listuploadoverlay.js')
    .js('resources/assets/js/admin/records/appuseractivationrecord.js', 'public/js/admin/records/appuseractivationrecord.js')
    .js('resources/assets/js/admin/records/baserecord.js', 'public/js/admin/records/baserecord.js')
    .js('resources/assets/js/admin/records/blacklistrecord.js', 'public/js/admin/records/blacklistrecord.js')
    .js('resources/assets/js/admin/records/deactivationrequestrecord.js', 'public/js/admin/records/deactivationrequestrecord.js')
    .js('resources/assets/js/admin/records/filterlistrecord.js', 'public/js/admin/records/filterlistrecord.js')
    .js('resources/assets/js/admin/records/grouprecord.js', 'public/js/admin/records/grouprecord.js')
    .js('resources/assets/js/admin/records/userrecord.js', 'public/js/admin/records/userrecord.js')
    .js('resources/assets/js/admin/records/whitelistrecord.js', 'public/js/admin/records/whitelistrecord.js')

