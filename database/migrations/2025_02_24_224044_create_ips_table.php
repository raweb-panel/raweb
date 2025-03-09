<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateIpsTable extends Migration
{
    public function up()
    {
        Schema::create('ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            // Use an ENUM field to restrict type to either ipv4 or ipv6.
            $table->enum('type', ['ipv4', 'ipv6'])->default('ipv4');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Fetch and insert the public IPv4 address if detected.
        $ipv4 = trim(@file_get_contents('https://api.ipify.org'));
        if ($ipv4 && filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            DB::table('ips')->insert([
                'ip_address'   => $ipv4,
                'type'         => 'ipv4',
                'description'  => 'Server Public IPv4 Address',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Fetch and insert the public IPv6 address if detected.
        $ipv6 = trim(@file_get_contents('https://api6.ipify.org'));
        if ($ipv6 && filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            DB::table('ips')->insert([
                'ip_address'   => $ipv6,
                'type'         => 'ipv6',
                'description'  => 'Server Public IPv6 Address',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('ips');
    }
}
