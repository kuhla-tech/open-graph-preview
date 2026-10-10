<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstanceSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'payload'];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }
}
