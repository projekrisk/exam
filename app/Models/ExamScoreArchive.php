<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamScoreArchive extends Model
{
    protected $fillable = [
        'exam_id',
        'question_bank_id',
        'subject_id',
        'subject_name',
        'teacher_id',
        'teacher_name',
        'finalized_by',
        'finalized_by_name',
        'exam_title',
        'session_name',
        'execution_mode',
        'exam_date',
        'total_participants',
        'total_questions',
        'objective_count',
        'essay_count',
        'max_violations',
        'visible_to_teacher',
        'visible_to_admin',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'datetime',
            'finalized_at' => 'datetime',
            'visible_to_teacher' => 'boolean',
            'visible_to_admin' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExamScoreArchiveItem::class);
    }
}
