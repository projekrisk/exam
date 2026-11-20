<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['nis', 'nisn', 'name', 'kelas', 'ruang', 'seat_number', 'is_active'];

    protected $hidden = ['nisn'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
