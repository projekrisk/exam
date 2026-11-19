<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionExcelImportService
{

    public function import(UploadedFile $file, QuestionBank $bank): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['File Excel kosong atau tidak memiliki baris soal.']];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap = $this->resolveColumnMap($header);

        if ($colMap['type'] === null || $colMap['question'] === null) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Header kolom wajib memuat minimal: Tipe dan Pertanyaan.'],
            ];
        }

        $nextOrder = ((int) $bank->questions()->max('order_no')) + 1;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $colMap, $bank, &$nextOrder, &$imported, &$skipped, &$errors) {
            foreach (array_slice($rows, 1) as $idx => $row) {
                $rowNum = $idx + 2;
                $rawType = strtolower(trim((string) ($row[$colMap['type']] ?? '')));
                $questionText = trim((string) ($row[$colMap['question']] ?? ''));

                if ($rawType === '' && $questionText === '') {
                    continue;
                }

                $type = $this->normalizeType($rawType);
                if (! $type || $questionText === '') {
                    $skipped++;
                    $errors[] = "Baris {$rowNum}: Tipe soal tidak dikenal atau Pertanyaan kosong.";
                    continue;
                }

                $weight = $colMap['weight'] !== null ? (float) ($row[$colMap['weight']] ?? 1) : 1.0;
                if ($weight <= 0) {
                    $weight = 1.0;
                }

                $rawMode = strtolower(trim((string) ($colMap['mode'] !== null ? ($row[$colMap['mode']] ?? '') : '')));
                $rawKey = trim((string) ($colMap['key'] !== null ? ($row[$colMap['key']] ?? '') : ''));

                $optionValues = [];
                foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
                    $idxCol = $colMap['opt_' . strtolower($letter)] ?? null;
                    $optionValues[$letter] = $idxCol !== null ? trim((string) ($row[$idxCol] ?? '')) : '';
                }

                [$options, $scoringMode] = $this->buildOptions($type, $optionValues, $rawKey, $rawMode);

                Question::create([
                    'question_bank_id' => $bank->id,
                    'type' => $type,
                    'question_text' => $questionText,
                    'options' => $options,
                    'scoring_mode' => $scoringMode,
                    'weight' => $weight,
                    'order_no' => $nextOrder++,
                ]);

                $imported++;
            }
        });

        Cache::forget("question_bank:{$bank->id}:questions");

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Soal');

        $sheet->fromArray([
            ['Tipe', 'Pertanyaan', 'Bobot', 'Mode_Skor', 'Opsi_A', 'Opsi_B', 'Opsi_C', 'Opsi_D', 'Opsi_E', 'Kunci'],
            ['pg', 'Berapakah hasil dari 5 x 6?', 1, 'full', '20', '25', '30', '35', '40', 'C'],
            ['pg_kompleks', 'Pilih bilangan prima di bawah ini:', 2, 'partial', '2', '4', '5', '9', '', 'A,C'],
            ['benar_salah', 'Tentukan Benar (B) atau Salah (S):', 2, 'full', 'Indonesia di Asia Tenggara', 'Ibu kota Jawa Barat adalah Surabaya', '', '', '', 'B,S'],
            ['uraian', 'Apa nama ibu kota Indonesia?', 2, 'auto_text', '', '', '', '', '', 'Jakarta, DKI Jakarta'],
            ['uraian', 'Jelaskan proses terjadinya hujan!', 5, 'manual', '', '', '', '', '', ''],
        ]);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempPath = storage_path('app/template_soal.xlsx');
        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    private function normalizeType(string $raw): ?string
    {
        return match ($raw) {
            'pg', 'pilihan ganda', 'pilihan_ganda' => 'pg',
            'pg_kompleks', 'pg kompleks', 'ganda kompleks', 'kompleks' => 'pg_kompleks',
            'benar_salah', 'benar/salah', 'bs', 'true_false' => 'benar_salah',
            'uraian', 'esai', 'essay' => 'uraian',
            default => null,
        };
    }

    private function buildOptions(string $type, array $optionValues, string $rawKey, string $rawMode): array
    {
        if ($type === 'pg' || $type === 'pg_kompleks') {
            $scoringMode = $rawMode === 'partial' ? 'partial' : 'full';
            $correctKeys = collect(preg_split('/[\s,;]+/', strtoupper($rawKey)))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            if ($type === 'pg' && count($correctKeys) > 1) {
                $correctKeys = [$correctKeys[0]];
            }

            $items = [];
            foreach ($optionValues as $letter => $text) {
                if ($text !== '') {
                    $items[] = [
                        'key' => $letter,
                        'text' => $text,
                        'image_path' => null,
                        'is_correct' => in_array($letter, $correctKeys, true),
                    ];
                }
            }

            return [$items, $scoringMode];
        }

        if ($type === 'benar_salah') {
            $scoringMode = $rawMode === 'partial' ? 'partial' : 'full';
            $keyTokens = collect(preg_split('/[\s,;]+/', strtoupper($rawKey)))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            $items = [];
            $i = 0;
            foreach ($optionValues as $text) {
                if ($text !== '') {
                    $token = $keyTokens[$i] ?? 'B';
                    $isTrue = in_array($token, ['B', 'BENAR', '1', 'T', 'TRUE', 'Y'], true);
                    $items[] = [
                        'key' => (string) ($i + 1),
                        'statement' => $text,
                        'correct' => $isTrue,
                    ];
                    $i++;
                }
            }

            return [$items, $scoringMode];
        }

        $mode = $rawMode === 'auto_text' || ($rawMode === '' && $rawKey !== '') ? 'auto_text' : 'manual';
        $accepted = $mode === 'auto_text'
            ? collect(preg_split('/[\r\n,;]+/', $rawKey))->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->values()->all()
            : [];

        return [[
            'mode' => $mode,
            'accepted_answers' => $accepted,
        ], 'full'];
    }

    private function resolveColumnMap(array $header): array
    {
        $find = function (array $candidates) use ($header): ?int {
            foreach ($header as $index => $col) {
                if (in_array($col, $candidates, true)) {
                    return $index;
                }
            }
            return null;
        };

        return [
            'type' => $find(['tipe', 'type', 'jenis']),
            'question' => $find(['pertanyaan', 'soal', 'question', 'question_text']),
            'weight' => $find(['bobot', 'weight', 'skor', 'nilai']),
            'mode' => $find(['mode_skor', 'mode', 'scoring_mode']),
            'opt_a' => $find(['opsi_a', 'a', 'pernyataan_1']),
            'opt_b' => $find(['opsi_b', 'b', 'pernyataan_2']),
            'opt_c' => $find(['opsi_c', 'c', 'pernyataan_3']),
            'opt_d' => $find(['opsi_d', 'd', 'pernyataan_4']),
            'opt_e' => $find(['opsi_e', 'e', 'pernyataan_5']),
            'key' => $find(['kunci', 'jawaban', 'key', 'answer']),
        ];
    }
}
