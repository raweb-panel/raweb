<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vhost extends Model
{
    use HasFactory;

    protected $fillable = [
        'file', 
        'server_name', 
        'http_port', 
        'ssl_port', 
        'php_version', 
        'log_type', 
        'ipv4', 
        'ipv6'
    ];
    
}
