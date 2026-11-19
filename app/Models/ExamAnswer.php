<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer_data',
        'is_doubtful',
        'client_updated_at',
        'idempotency_key',
        'score',
        'is_graded',
        'teacher_note',
    ];

    protected function casts(): array
    {
        return [
            'answer_data' => 'array',
            'is_doubtful' => 'boolean',
            'is_graded' => 'boolean',
            'score' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
