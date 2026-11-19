<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'device_id',
        'question_order',
        'option_orders',
        'status',
        'violation_count',
        'started_at',
        'must_end_at',
        'last_sync_at',
        'submitted_at',
        'objective_score',
        'essay_score',
        'final_score',
        'manual_score',
        'teacher_note',
        'grading_status',
        'receipt_code',
    ];

    protected function casts(): array
    {
        return [
            'question_order' => 'array',
            'option_orders' => 'array',
            'started_at' => 'datetime',
            'must_end_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'submitted_at' => 'datetime',
            'objective_score' => 'decimal:2',
            'essay_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'manual_score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class, 'attempt_id');
    }
}
