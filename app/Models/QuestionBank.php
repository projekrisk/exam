<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    protected $fillable = ['subject_id', 'teacher_id', 'title', 'description', 'target_classes', 'is_ready', 'revision_note', 'revision_requested_at'];

    protected function casts(): array
    {
        return [
            'target_classes' => 'array',
            'is_ready' => 'boolean',
            'revision_requested_at' => 'datetime',
        ];
    }

    public function isAvailableForClass(string $kelas): bool
    {
        $targets = $this->target_classes ?? [];
        if (empty($targets) || in_array('ALL', $targets, true)) {
            return true;
        }

        return in_array($kelas, $targets, true);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_no')->orderBy('id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function isLocked(): bool
    {
        return ($this->exams_count ?? $this->exams()->count()) > 0;
    }
}
