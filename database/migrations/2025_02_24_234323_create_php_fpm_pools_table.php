<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhpFpmPoolsTable extends Migration
{
    public function up()
    {
        Schema::create('php_fpm_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // new dedicated pool name
            $table->string('version');         // PHP version, e.g., 8.1
            $table->string('type')->default('ip'); // "ip" or "sock"
            $table->string('listen');          // if type "ip": "127.0.0.1:9000", if sock then file path
            $table->string('user');            // user for pool
            $table->unsignedInteger('pm_max_children');
            $table->unsignedInteger('pm_max_requests');
            $table->string('ram_limit');       // memory limit, e.g.: "128M"
            $table->unsignedInteger('max_vars'); // max input vars
            $table->unsignedInteger('max_execution_time'); // seconds
            $table->string('max_upload');      // upload max size, e.g.: "50M"
            $table->boolean('display_errors')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('php_fpm_pools');
    }
}
