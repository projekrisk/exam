<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExamExcelExportService
{
    /**
     * Build a multi-sheet (one sheet per class) Excel workbook with an editable
     * essay score column and automatic Excel formula for the final score.
     *
     * @param  array{title:string, subject:string, mode:string, schedule:string, total_questions:int, objective_count:int, essay_count:int, max_violations:int}  $meta
     * @param  Collection<int, array{nis:string, name:string, kelas:string, ruang:string, answered_count:int, objective_correct:int, objective_wrong:int, objective_score:float|int, essay_score:float|int, violation_count:int, status_label:string, grading_label:string}>  $rows
     */
    public function buildWorkbook(array $meta, Collection $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $grouped = $rows->groupBy(fn (array $row) => trim((string) ($row['kelas'] ?? '')) !== '' ? (string) $row['kelas'] : 'Tanpa Kelas');
        if ($grouped->isEmpty()) {
            $grouped = collect(['Rekap Nilai' => collect()]);
        }

        $sortedClasses = $grouped->keys()->sort(fn (string $a, string $b) => $this->compareClassNames($a, $b))->values();
        $usedTitles = [];

        foreach ($sortedClasses as $className) {
            $sheet = new Worksheet($spreadsheet, $this->uniqueSheetTitle($className, $usedTitles));
            $spreadsheet->addSheet($sheet);

            $classRows = $grouped->get($className, collect())->values();
            $this->populateClassSheet($sheet, $meta, $className, $classRows);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function populateClassSheet(Worksheet $sheet, array $meta, string $className, Collection $rows): void
    {
        $sheet->setCellValue('A1', 'REKAP NILAI UJIAN - ' . strtoupper($meta['title']));
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue(
            'A2',
            'Kelas: ' . $className
            . '  |  Mata Pelajaran: ' . $meta['subject']
            . '  |  Mode: ' . $meta['mode']
            . '  |  Jadwal: ' . $meta['schedule']
        );
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $sheet->setCellValue(
            'A3',
            "Total Soal: {$meta['total_questions']} (Objektif: {$meta['objective_count']}, Uraian: {$meta['essay_count']})  |  Peserta Kelas Ini: " . $rows->count()
            . '  |  Catatan: Isi kolom Skor Uraian (Manual), Nilai Akhir terhitung otomatis (=Objektif+Uraian).'
        );
        $sheet->mergeCells('A3:N3');
        $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $headerRow = 5;
        $headers = [
            'No',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Ruang',
            'Dijawab',
            'Benar Objektif',
            'Salah / Kurang',
            'Skor Objektif',
            'Skor Uraian (Manual)',
            'Nilai Akhir (Rumus)',
            'Pelanggaran',
            'Status Ujian',
            'Status Koreksi',
        ];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->getStyle("A{$headerRow}:N{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EFF6FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("J{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FEF9C3');

        $rowIndex = $headerRow + 1;
        foreach ($rows as $idx => $row) {
            $sheet->fromArray([
                $idx + 1,
                $row['nis'],
                $row['name'],
                $row['kelas'],
                $row['ruang'],
                "{$row['answered_count']} / {$meta['total_questions']}",
                "{$row['objective_correct']} / {$meta['objective_count']}",
                (int) $row['objective_wrong'],
                (int) round((float) $row['objective_score']),
                (int) round((float) $row['essay_score']),
                "=I{$rowIndex}+J{$rowIndex}",
                "{$row['violation_count']} / {$meta['max_violations']}",
                $row['status_label'],
                $row['grading_label'],
            ], null, "A{$rowIndex}");

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Highlight editable manual essay column
            $sheet->getStyle("J{$rowIndex}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FEFCE8');

            $rowIndex++;
        }

        $lastRow = max($headerRow, $rowIndex - 1);

        $sheet->getStyle("A{$headerRow}:N{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '94A3B8'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($lastRow > $headerRow) {
            $dataStart = $headerRow + 1;
            $sheet->getStyle("A{$dataStart}:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$dataStart}:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$dataStart}:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$dataStart}:K{$lastRow}")->getNumberFormat()->setFormatCode('0');
            $sheet->getStyle("I{$dataStart}:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$dataStart}:K{$lastRow}")->getFont()->setBold(true);
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function uniqueSheetTitle(string $rawTitle, array &$usedTitles): string
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', trim($rawTitle)) ?: 'Rekap';
        $base = mb_substr($clean, 0, 31);
        $candidate = $base;
        $counter = 2;

        while (in_array(mb_strtolower($candidate), $usedTitles, true)) {
            $suffix = " ({$counter})";
            $candidate = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $counter++;
        }

        $usedTitles[] = mb_strtolower($candidate);

        return $candidate;
    }

    private function compareClassNames(string $a, string $b): int
    {
        $rank = fn (string $k): int => match (true) {
            preg_match('/^XII(?![I])/i', $k) === 1 => 12,
            preg_match('/^XI(?![I])/i', $k) === 1 => 11,
            preg_match('/^X(?![IV])/i', $k) === 1 => 10,
            default => 99,
        };
        $ra = $rank($a);
        $rb = $rank($b);

        return $ra !== $rb ? $ra <=> $rb : strnatcasecmp($a, $b);
    }
}
