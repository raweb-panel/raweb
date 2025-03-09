<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToPortsTable extends Migration
{
    public function up()
    {
        Schema::table('ports', function (Blueprint $table) {
            $table->string('type')->default('http'); // 'http' or 'ssl'
        });
    }

    public function down()
    {
        Schema::table('ports', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
