<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    // Encrypt the value attribute for sensitive data
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = Crypt::encryptString($value);
    }

    // Decrypt the value attribute when accessing
    public function getValueAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}
