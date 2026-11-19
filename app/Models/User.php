<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'name', 'email', 'role', 'nip', 'password', 'must_change_password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{

    use HasFactory, Notifiable;

    public const DEFAULT_TEACHER_PASSWORD = 'password123';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_user')->withPivot('kelas');
    }

    public function assignedClassesForSubject(int $subjectId): array
    {
        return $this->subjects()
            ->where('subjects.id', $subjectId)
            ->pluck('subject_user.kelas')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function allAssignedClasses(): array
    {
        return $this->subjects()
            ->pluck('subject_user.kelas')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'teacher_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'panitia'], true);
    }

    public function isPanitia(): bool
    {
        return in_array($this->role, ['admin', 'panitia'], true);
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isProktor(): bool
    {
        return $this->role === 'proktor';
    }
}
