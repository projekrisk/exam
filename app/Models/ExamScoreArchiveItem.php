<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamScoreArchiveItem extends Model
{
    protected $fillable = [
        'exam_score_archive_id',
        'student_id',
        'student_nis',
        'student_nisn',
        'student_name',
        'student_kelas',
        'student_ruang',
        'answered_count',
        'objective_correct',
        'objective_wrong',
        'objective_score',
        'essay_score',
        'manual_score',
        'final_score',
        'violation_count',
        'attempt_status',
        'grading_status',
        'teacher_note',
    ];

    protected function casts(): array
    {
        return [
            'objective_score' => 'decimal:2',
            'essay_score' => 'decimal:2',
            'manual_score' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    public function archive(): BelongsTo
    {
        return $this->belongsTo(ExamScoreArchive::class, 'exam_score_archive_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
