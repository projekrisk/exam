<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'question_bank_id',
        'subject_id',
        'created_by',
        'execution_mode',
        'target_classes',
        'session_name',
        'start_at',
        'end_at',
        'duration_minutes',
        'min_submit_remaining_minutes',
        'token',
        'require_token',
        'token_refresh_minutes',
        'token_updated_at',
        'shuffle_questions',
        'shuffle_options',
        'violation_mode',
        'max_violations',
        'show_score',
        'scores_released',
        'scores_released_at',
        'scores_finalized',
        'scores_finalized_at',
        'scores_finalized_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_classes' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'token_updated_at' => 'datetime',
            'scores_released_at' => 'datetime',
            'scores_finalized_at' => 'datetime',
            'require_token' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_score' => 'boolean',
            'scores_released' => 'boolean',
            'scores_finalized' => 'boolean',
        ];
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'draft') {
            return 'draft';
        }

        if ($this->status === 'finished') {
            return 'finished';
        }

        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) {
            return 'waiting';
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return 'finished';
        }

        return 'ongoing';
    }

    public function statusBadge(): array
    {
        return match ($this->effectiveStatus()) {
            'draft' => [
                'label' => 'Draft',
                'class' => 'bg-notion-hover text-notion-gray border border-notion-border',
            ],
            'waiting' => [
                'label' => 'Menunggu',
                'class' => 'bg-amber-50 text-amber-800 border border-amber-200',
            ],
            'ongoing' => [
                'label' => 'Berlangsung',
                'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            ],
            default => [
                'label' => 'Selesai',
                'class' => 'bg-notion-hover text-notion-gray border border-notion-border',
            ],
        };
    }

    public static function generateToken(int $length = 6): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($alphabet) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= $alphabet[random_int(0, $maxIndex)];
        }

        return $token;
    }

    public function refreshTokenIfNeeded(): void
    {
        if (! $this->require_token || ! $this->token_refresh_minutes || $this->effectiveStatus() !== 'ongoing') {
            return;
        }

        $now = now();
        $lastUpdated = $this->token_updated_at ?? $this->updated_at ?? $this->created_at;

        if (! $lastUpdated || $lastUpdated->copy()->addMinutes((int) $this->token_refresh_minutes)->lte($now)) {
            $this->update([
                'token' => self::generateToken(),
                'token_updated_at' => $now,
            ]);
        }
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function scoreArchive(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ExamScoreArchive::class);
    }

    public function isAvailableForClass(string $kelas): bool
    {
        if ($this->questionBank && ! $this->questionBank->isAvailableForClass($kelas)) {
            return false;
        }

        $targets = $this->target_classes ?? [];
        if (empty($targets) || in_array('ALL', $targets, true)) {
            return true;
        }

        return in_array($kelas, $targets, true);
    }
}
