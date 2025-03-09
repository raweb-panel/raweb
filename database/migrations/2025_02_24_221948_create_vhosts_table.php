<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVhostsTable extends Migration
{
    public function up()
    {
        Schema::create('vhosts', function (Blueprint $table) {
            $table->id();
            $table->string('file')->unique();
            $table->string('server_name');
            $table->integer('http_port');
            $table->integer('ssl_port')->nullable();
            $table->string('php_version')->default('8.4');
            $table->string('log_type')->default('main');
            $table->string('ipv4')->nullable(); // Add ipv4 column
            $table->string('ipv6')->nullable(); // Add ipv6 column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vhosts');
    }
}
