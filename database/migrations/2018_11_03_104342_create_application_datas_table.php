<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicationDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->string("name");
            $table->ipAddress("ip");
            $table->string("coinbase")->nullable();
            $table->string("wallet_name");
            $table->string("authenticity_endpoint", 240);
            $table->string("notify_endpoint", 240);
            $table->string("sign_endpoint", 240);
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
        Schema::dropIfExists('application_datas');
    }
}
