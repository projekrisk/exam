<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;

class ExamScoringService
{

    public function gradeAttempt(ExamAttempt $attempt): ExamAttempt
    {
        $questions = $attempt->exam->questionBank->questions()->get()->keyBy('id');
        $answers = $attempt->answers()->get()->keyBy('question_id');

        $totalWeight = 0.0;
        $objectiveEarned = 0.0;
        $essayEarned = 0.0;
        $hasUngradedEssay = false;

        foreach ($questions as $questionId => $question) {
            $weight = (float) $question->weight;
            $totalWeight += $weight;

            $answer = $answers->get($questionId);

            if ($question->type === 'uraian') {
                if ($answer && $answer->is_graded) {
                    $essayEarned += (float) $answer->score;
                } elseif (($question->options['mode'] ?? 'manual') === 'auto_text') {
                    $earned = $answer ? $this->scoreAutoEssay($question, $answer->answer_data ?? []) : 0.0;
                    if ($answer) {
                        $answer->update([
                            'score' => $earned,
                            'is_graded' => true,
                        ]);
                    }
                    $essayEarned += $earned;
                } else {
                    $hasUngradedEssay = true;
                }
                continue;
            }

            $earned = $answer ? $this->scoreObjectiveQuestion($question, $answer->answer_data ?? []) : 0.0;

            if ($answer) {
                $answer->update([
                    'score' => $earned,
                    'is_graded' => true,
                ]);
            }

            $objectiveEarned += $earned;
        }

        $objectiveNormalized = $totalWeight > 0 ? round(($objectiveEarned / $totalWeight) * 100, 2) : 0.0;
        $essayNormalized = $totalWeight > 0 ? round(($essayEarned / $totalWeight) * 100, 2) : 0.0;
        $finalScore = round($objectiveNormalized + $essayNormalized, 2);

        $attempt->update([
            'objective_score' => $objectiveNormalized,
            'essay_score' => $essayNormalized,
            'final_score' => $finalScore,
            'grading_status' => $hasUngradedEssay ? 'pending_essay' : 'final',
        ]);

        return $attempt->fresh();
    }

    public function scoreObjectiveQuestion(Question $question, array $answerData): float
    {
        $weight = (float) $question->weight;
        $options = $question->options ?? [];

        return match ($question->type) {
            'pg' => $this->scoreSingleChoice($options, $answerData, $weight),
            'pg_kompleks' => $this->scoreMultipleChoice($options, $answerData, $weight, $question->scoring_mode),
            'benar_salah' => $this->scoreTrueFalse($options, $answerData, $weight, $question->scoring_mode),
            default => 0.0,
        };
    }

    public function scoreAutoEssay(Question $question, array $answerData): float
    {
        $accepted = $question->options['accepted_answers'] ?? [];
        $rawText = (string) ($answerData['text'] ?? '');
        $normalizedStudent = $this->normalizeText($rawText);

        if ($normalizedStudent === '' || empty($accepted)) {
            return 0.0;
        }

        foreach ($accepted as $candidate) {
            if ($this->normalizeText((string) $candidate) === $normalizedStudent) {
                return (float) $question->weight;
            }
        }

        return 0.0;
    }

    private function normalizeText(string $text): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($text), 'UTF-8')) ?? '';
    }

    private function scoreSingleChoice(array $options, array $answerData, float $weight): float
    {
        $selected = $answerData['selected'] ?? null;
        if (! is_string($selected)) {
            return 0.0;
        }

        foreach ($options as $opt) {
            if (($opt['key'] ?? null) === $selected && ! empty($opt['is_correct'])) {
                return $weight;
            }
        }

        return 0.0;
    }

    private function scoreMultipleChoice(array $options, array $answerData, float $weight, string $mode): float
    {
        $selected = $answerData['selected'] ?? [];
        if (! is_array($selected) || empty($selected)) {
            return 0.0;
        }

        $correctKeys = collect($options)
            ->filter(fn ($opt) => ! empty($opt['is_correct']))
            ->pluck('key')
            ->values()
            ->all();

        if (empty($correctKeys)) {
            return 0.0;
        }

        $selectedUnique = array_values(array_unique(array_map('strval', $selected)));
        sort($correctKeys);
        sort($selectedUnique);

        if ($selectedUnique === $correctKeys) {
            return $weight;
        }

        if ($mode === 'partial') {
            $rightCount = count(array_intersect($selectedUnique, $correctKeys));
            $wrongCount = count(array_diff($selectedUnique, $correctKeys));
            $net = max(0, $rightCount - $wrongCount);
            return round(($net / count($correctKeys)) * $weight, 2);
        }

        return 0.0;
    }

    private function scoreTrueFalse(array $statements, array $answerData, float $weight, string $mode): float
    {
        $responses = $answerData['statements'] ?? [];
        if (! is_array($responses) || empty($statements)) {
            return 0.0;
        }

        $total = count($statements);
        $correctCount = 0;

        foreach ($statements as $stmt) {
            $key = (string) ($stmt['key'] ?? '');
            $expected = (bool) ($stmt['correct'] ?? false);
            if (array_key_exists($key, $responses) && (bool) $responses[$key] === $expected) {
                $correctCount++;
            }
        }

        if ($correctCount === $total) {
            return $weight;
        }

        if ($mode === 'partial' && $total > 0) {
            return round(($correctCount / $total) * $weight, 2);
        }

        return 0.0;
    }
}
