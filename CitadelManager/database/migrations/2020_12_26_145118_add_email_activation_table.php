<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmailActivationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_activation_urls', function (Blueprint $table) {
            $table->string('hash')->primary();

            // The name of the user. Required. Could be just a first name,
            // a full name, or last name, whatever.
            $table->json('login_request');

            $table->timestamp("expired_at")->index();
            // Created at/modified at.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_activation_urls');
    }
}
