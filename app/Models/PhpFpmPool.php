<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpFpmPool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'type',
        'listen',
        'user',
        'pm_max_children',
        'pm_max_requests',
        'ram_limit',
        'max_vars',
        'max_execution_time',
        'max_upload',
        'display_errors',
    ];
}
