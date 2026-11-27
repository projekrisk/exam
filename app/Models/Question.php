<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'question_bank_id',
        'type',
        'question_text',
        'media_path',
        'options',
        'scoring_mode',
        'weight',
        'order_no',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'weight' => 'decimal:2',
        ];
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function toStudentPayload(?array $optionOrder = null): array
    {
        $rawOptions = $this->options ?? [];

        $sanitizedOptions = match ($this->type) {
            'pg', 'pg_kompleks' => collect($rawOptions)
                ->map(fn (array $opt) => [
                    'key' => $opt['key'],
                    'text' => $opt['text'] ?? '',
                    'image_url' => ! empty($opt['image_path']) ? asset('uploads/soal/' . $opt['image_path']) : null,
                ])
                ->when($optionOrder, function ($col) use ($optionOrder) {
                    $rank = array_flip($optionOrder);
                    return $col->sortBy(fn ($item) => $rank[$item['key']] ?? 999)->values();
                })
                ->values()
                ->all(),
            'benar_salah' => collect($rawOptions)
                ->map(fn (array $stmt) => [
                    'key' => $stmt['key'],
                    'statement' => $stmt['statement'],
                ])
                ->values()
                ->all(),
            'menjodohkan' => (function () use ($rawOptions) {
                $rights = collect($rawOptions)
                    ->pluck('right')
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => $v !== '')
                    ->values()
                    ->shuffle()
                    ->values()
                    ->all();

                return collect($rawOptions)
                    ->values()
                    ->map(fn (array $pair, int $idx) => [
                        'key' => (string) ($pair['key'] ?? ($idx + 1)),
                        'statement' => (string) ($pair['left'] ?? ''),
                        'text' => $rights[$idx] ?? '',
                    ])
                    ->all();
            })(),
            default => null,
        };

        return [
            'id' => $this->id,
            'type' => $this->type,
            'question_text' => $this->question_text,
            'media_url' => $this->media_path ? asset('uploads/soal/' . $this->media_path) : null,
            'options' => $sanitizedOptions,
            'uraian_mode' => $this->type === 'uraian' ? ($rawOptions['mode'] ?? 'manual') : null,
            'weight' => (float) $this->weight,
        ];
    }
}
