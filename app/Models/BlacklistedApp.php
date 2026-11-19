<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedApp extends Model
{
    protected $fillable = [
        'app_name',
        'package_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
