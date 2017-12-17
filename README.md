# CloudVeil Manager

This is a web based manager to control [CloudVeil For Windows](https://github.com/cloudveiltech/Citadel-Windows)

This content is based on the [Citadel Manager Wiki](https://github.com/TechnikEmpire/CitadelManager/wiki). Thanks to [Jesse Nicholson](https://github.com/TechnikEmpire)!

## Index

* [Dev Getting Started Guide](#Dev-Getting-Started-Guide)
* [Installation Guide](#Installation-Guide)

## Dev Getting Started Guide

This project uses Netbeans PHP/HTML5/JS for Laravel/PHP dev, and VS Code for Typescript development of the UI JS code.

Prerequisites:
 - Netbeans PHP/HTML5/JS, or an editor of your choosing for PHP.
 - Node v4.5.0 or later (`node -v`)
 - Typings v2.1.1 or later (`typings -v`)
 - Visual Studio Code with Typescript 2.0.10 or later (`tsc -v`)
 - Composer v1.2.1 or later (`composer --version`)

To get started, you'll need to run the following commands from the nested `.\CitadelManager`directory:

`npm install`
`typings install`

Once this is complete, you should be able to build the Typescript source from Visual Studio Code by running the build task command:  
 `> Run Build Task`

For Laravel/PHP, just run:  
 `composer install`

## Installation Guide

In this article, we're going to go through a step-by-step process to get the web application installed and configured correctly on your server.

### Prerequisites 
---
 - PHP >= 7.0.*
 - PHP 7 ZIP package. Something like `php7.0-zip`
 - MySQL compatible backend for PHP, such as MySQL or MariaDB.
 - Java JDK/JRE 8 or later, or compatible (openJDK, etc).

Some features that are specific to PHP7 are used, hence the requirement for 7.0 or greater. User data payloads are generated and packed into `.zip` files, hence the php 7 zip extension so we have the `ZipArchive` class available to use. 

As far as MySQL, while Laravel does support several DB drivers, it doesn't provide a driver agnostic method to specify `MEDIUMBLOB` or `LARGEBLOB` database columns. `MEDIUMBLOB` is required by this application. As such, we had to write some MySQL specific raw queries to alter tables after creation.

The manager app allows for the upload and configuration of Apache OpenNLP Natural Language Processing models (doccat models specifically). Specific categories from the uploaded model can be assigned to a group. As such, Java is required so that the application can execute a bundled JAR needed to extract category names from uploaded models.

### Not Covered Here  
---
In this article, we will not cover the configuration of your web server. For ease of use, it is recommended that you use Apache2, as there are `.htaccess` files created to auto configure PHP with optimal settings for this application. The only thing I will say, is that you should ensure that whatever URL you assign as the base location for this application, should be linked to the `CitadelManager/public` directory. This is simply following the Laravel Way™ for server structure. It should be the only directory exposed to the public.

#### Special Note For Localhost dev  
If you leave the APP_URL, SESSION_DOMAIN and DOMAIN keys as their default value (covered below), just add an entry to your local hosts file for `laravel.app`, like so on Windows:

`127.0.0.1 laravel.app`

Then use `http://laravel.app` to test/build. You'll avoid a lot of hard to debug issues, especially if you use Chrome.

### Running The Install
---

First, set up a new, blank MySQL database and add a user with rights to that database. Remember the DB name, user and password for later on.

Next, pull down the missing dependencies linked in to the project via Composer. In the `CitadelManager` directory, run:  

`composer install`

Once this process completes, you'll want to copy the included `.env.example` file to `.env` in the same directory, like so:

`cp .env.example .env`

Next, you'll want to generate a new app key. From within the same directory, run:  

`php artisan key:generate`

You'll need to run a migration to create the oauth tables.
`php artisan migrate`

This command takes care of generating oauth keys.
`php artisan passport:install`

Before starting the guided installation process, you should manually edit the the `.env` and set the following values correctly:

 - `APP_URL=http://laravel.app` // Set this to the correct URL.
 - `SESSION_DOMAIN=.laravel.app` // Set this to your real domain.
 - `DOMAIN=.laravel.app` // Set this to your real domain.

If you don't, you'll get a csfr token mismatch exception when you try to update your `.env` file from the guided installer.

Ensure your server has write permissions for `CitadelManager/storage` and `CitadelManager/boostrap/cache`. Lastly, ensure it can write to your `.env` file.

Example:
```bash
chmod 775 resources/
chmod 775 app
chmod 775 bootstrap/cache/
chmod 775 storage/
chmod 775 .env
```

Now, you can go ahead and navigate in a browser of your choice to the URL which points to the app, pointing to the resource `install`. For the purposes of this article, we'll pretend your URL is `http://laravel.app`, so given that, you'll want to navigate to `http://laravel.app/install`.

You should be greeted with a three stage installation process where you are free to configure your new app installation. Click next until you get to the `Environment Settings` step. Here, there are a few things you're going to want and need to set or change.

 - `APP_NAME=Citadel` // The name of your app.
 - `ADMIN_NAME=` // Set this to your real name.
 - `ADMIN_EMAIL=` // Set this to your email address.
 - `ADMIN_PASSWORD=` // Set this to your desired password.
 - `SESSION_SECURE_COOKIE=false` // Set this to true if you're enforcing HTTPS, which you should be.
 - `DB_*` // Set all of the DB variables correctly. Use the DB name, user and password you set up earlier.

# CLICK THE 'SAVE .ENV' BUTTON!!!

After saving the .env file with your proper settings, go ahead and "next" all the way through until the install is complete. The installer will notify you if any other prerequisites are missing, or if folder permissions are incorrect.

Once the installer is finished, you will be redirected to the login page. Sign in with the admin credentials you set in your ENV file.

### Closing Notes  
---
If you do not configure the first admin account via the .ENV variables when using the installer interface, an admin account will be generated automatically from random strings. However, since you used the installer UI, you will not have seen the output of this action. If you missed this step, but don't want to re-run through installer process, you can run the following command from with the `CitadelManager` directory:

`php artisan migrate:refresh --seed`

**This will drop all of the database tables** and re-seed them, and the generated admin account credentials will be printed to the console. You **must** change these after your first login. Notice again, **this will drop all of the database tables**. It is recommended that you instead take advantage of the .ENV vars during the visual installer process.

If you are messing about with running migrations and re-seeding, etc, especially after you've had a session, you should generally run the cache clear command. 

`php artisan cache:clear`