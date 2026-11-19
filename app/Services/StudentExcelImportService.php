<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentExcelImportService
{

    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['File Excel kosong atau tidak memiliki baris data.']];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap = $this->resolveColumnMap($header);

        if ($colMap['nis'] === null || $colMap['nisn'] === null || $colMap['name'] === null || $colMap['kelas'] === null) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Header kolom wajib memuat: NIS, NISN, Nama, dan Kelas.'],
            ];
        }

        $now = now();
        $records = [];
        $skipped = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $idx => $row) {
            $rowNumber = $idx + 2;
            $nis = trim((string) ($row[$colMap['nis']] ?? ''));
            $nisn = trim((string) ($row[$colMap['nisn']] ?? ''));
            $name = trim((string) ($row[$colMap['name']] ?? ''));
            $kelas = trim((string) ($row[$colMap['kelas']] ?? ''));
            $ruang = $colMap['ruang'] !== null ? trim((string) ($row[$colMap['ruang']] ?? '')) : '';

            if ($nis === '' && $nisn === '' && $name === '') {
                continue;
            }

            if ($nis === '' || $nisn === '' || $name === '' || $kelas === '') {
                $skipped++;
                $errors[] = "Baris {$rowNumber}: NIS, NISN, Nama, atau Kelas tidak boleh kosong.";
                continue;
            }

            $records[$nis] = [
                'nis' => $nis,
                'nisn' => $nisn,
                'name' => $name,
                'kelas' => $kelas,
                'ruang' => $ruang !== '' ? $ruang : null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $imported = 0;
        foreach (array_chunk(array_values($records), 500) as $chunk) {
            Student::upsert($chunk, ['nis'], ['nisn', 'name', 'kelas', 'ruang', 'is_active', 'updated_at']);
            $imported += count($chunk);
        }

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
        $sheet->setTitle('Data Siswa');

        $sheet->fromArray([
            ['NIS', 'NISN', 'Nama', 'Kelas', 'Ruang'],
            ['24001', '0081234567', 'Ahmad Fauzan', 'X-1', 'Ruang 1'],
            ['24002', '0081234568', 'Siti Aminah', 'XI IPA 1', 'Ruang 2'],
        ]);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempPath = storage_path('app/template_siswa.xlsx');
        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
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
            'nis' => $find(['nis', 'nomor induk', 'no induk']),
            'nisn' => $find(['nisn']),
            'name' => $find(['nama', 'nama lengkap', 'nama siswa', 'name']),
            'kelas' => $find(['kelas', 'rombel', 'class']),
            'ruang' => $find(['ruang', 'ruangan', 'room', 'ruang ujian']),
        ];
    }
}
