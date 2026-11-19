<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Nilai PDF | {{ $scoreArchive->exam_title }}</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Bricolage Grotesque", sans-serif;
      color: #000000;
      background: #f3f4f6;
      font-size: 10pt;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

    .toolbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: #ffffff;
      border-bottom: 1px solid #e5e5e5;
      padding: 12px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .toolbar-info { font-size: 13px; color: #374151; }
    .toolbar-actions { display: flex; align-items: center; gap: 8px; }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      font-family: inherit;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid #d1d5db;
      background: #ffffff;
      color: #111827;
    }
    .btn-primary { background: #2563eb; border-color: #2563eb; color: #ffffff; }

    .page-wrap { padding: 18px; display: flex; justify-content: center; }
    .paper {
      width: 210mm;
      min-height: 297mm;
      padding: 12mm 14mm;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .kop {
      display: flex;
      align-items: center;
      gap: 4mm;
      padding-bottom: 3mm;
      border-bottom: 2px solid #000000;
    }
    .kop-logo { width: 15mm; height: 15mm; object-fit: contain; flex-shrink: 0; }
    .kop-text { flex: 1; text-align: left; line-height: 1.2; }
    .kop-prov { font-size: 9pt; font-weight: 600; text-transform: uppercase; }
    .kop-dinas { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; }
    .kop-school { font-size: 12.5pt; font-weight: 800; text-transform: uppercase; margin-top: 0.5mm; }

    .doc-heading { text-align: center; padding: 4mm 0 3mm; }
    .doc-title { font-size: 12pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px; }
    .doc-sub { font-size: 9.5pt; font-weight: 600; margin-top: 0.6mm; }

    .meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2mm 8mm;
      margin-bottom: 3.5mm;
      font-size: 8.8pt;
    }
    .meta-table { width: 100%; border-collapse: collapse; }
    .meta-table td { padding: 0.5mm 0; vertical-align: top; }
    .meta-table td.lbl { width: 28mm; }
    .meta-table td.sep { width: 3mm; text-align: center; }
    .meta-table td.val { font-weight: 700; }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 2.5mm;
      margin-bottom: 4mm;
    }
    .stat-box {
      border: 0.75px solid #000000;
      border-radius: 3px;
      padding: 1.8mm 2.5mm;
      text-align: center;
    }
    .stat-lbl { font-size: 7.5pt; text-transform: uppercase; }
    .stat-val { font-size: 11pt; font-weight: 800; margin-top: 0.4mm; }

    .score-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8.3pt;
    }
    .score-table thead { display: table-header-group; }
    .score-table tr { break-inside: avoid; }
    .score-table th, .score-table td {
      border: 0.75px solid #000000;
      padding: 1.4mm 1.8mm;
      vertical-align: middle;
    }
    .score-table th {
      background: #f3f4f6;
      font-weight: 700;
      text-align: center;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: 700; }

    .sign-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12mm;
      margin-top: 8mm;
      break-inside: avoid;
      font-size: 9pt;
    }
    .sign-block { line-height: 1.3; }
    .sign-qr { width: 13mm; height: 13mm; margin: 1.5mm 0; }
    .sign-qr svg { width: 100%; height: 100%; display: block; }
    .sign-space { height: 15mm; }
    .sign-name { font-weight: 700; text-decoration: underline; }

    @page { size: A4 portrait; margin: 12mm; }
    @media print {
      body { background: #ffffff; }
      .no-print { display: none !important; }
      .page-wrap { padding: 0; }
      .paper { width: 100%; min-height: auto; padding: 0; box-shadow: none; }
    }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <div class="toolbar-info">
      <strong>Pratinjau PDF Daftar Nilai</strong> · {{ $scoreArchive->exam_title }} ({{ $stats['count'] }} peserta)
    </div>
    <div class="toolbar-actions">
      <a href="{{ route('score-archives.show', $scoreArchive) }}" class="btn">Kembali</a>
      <button type="button" onclick="window.print()" class="btn btn-primary">Simpan PDF / Cetak (Ctrl+P)</button>
    </div>
  </div>

  <div class="page-wrap">
    <div class="paper">
      <header class="kop">
        @if(!empty($settings['school_logo_path']))
          <img src="{{ asset('uploads/soal/' . $settings['school_logo_path']) }}" alt="Logo" class="kop-logo">
        @endif
        <div class="kop-text">
          <div class="kop-prov">Pemerintah Provinsi Banten</div>
          <div class="kop-dinas">Dinas Pendidikan dan Kebudayaan</div>
          <div class="kop-school">{{ $settings['school_name'] ?: 'SMAN 1 Malingping' }}</div>
        </div>
      </header>

      <div class="doc-heading">
        <div class="doc-title">Daftar Nilai Peserta Ujian</div>
        <div class="doc-sub">{{ $settings['activity_name'] ?: 'Penilaian Sumatif Akhir Semester' }} · Tahun Ajaran {{ $settings['academic_year'] ?: '2026/2027' }}</div>
      </div>

      <div class="meta-grid">
        <table class="meta-table">
          <tr><td class="lbl">Nama Ujian</td><td class="sep">:</td><td class="val">{{ $scoreArchive->exam_title }}</td></tr>
          <tr><td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td class="val">{{ $scoreArchive->subject_name }}</td></tr>
          <tr><td class="lbl">Kelas Filter</td><td class="sep">:</td><td class="val">{{ $kelas !== '' ? $kelas : 'Semua Kelas' }}</td></tr>
        </table>
        <table class="meta-table">
          <tr><td class="lbl">Pembuat Soal</td><td class="sep">:</td><td class="val">{{ $scoreArchive->teacher_name }}</td></tr>
          <tr><td class="lbl">Mode Ujian</td><td class="sep">:</td><td class="val">{{ $scoreArchive->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri' }}</td></tr>
          <tr><td class="lbl">Difinalisasi</td><td class="sep">:</td><td class="val">{{ $scoreArchive->finalized_at?->translatedFormat('d M Y H:i') }} WIB</td></tr>
        </table>
      </div>

      <div class="stats-row">
        <div class="stat-box">
          <div class="stat-lbl">Jumlah Peserta</div>
          <div class="stat-val">{{ $stats['count'] }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">Rata-rata</div>
          <div class="stat-val">{{ number_format((float) $stats['avg'], 2) }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">Nilai Tertinggi</div>
          <div class="stat-val">{{ number_format((float) $stats['max'], 2) }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">Nilai Terendah</div>
          <div class="stat-val">{{ number_format((float) $stats['min'], 2) }}</div>
        </div>
      </div>

      <table class="score-table">
        <thead>
          <tr>
            <th style="width: 8mm;">No</th>
            <th style="width: 22mm;">NIS</th>
            <th>Nama Siswa</th>
            <th style="width: 17mm;">Kelas</th>
            <th style="width: 15mm;">Ruang</th>
            <th style="width: 16mm;">Dijawab</th>
            <th style="width: 15mm;">Objektif</th>
            <th style="width: 14mm;">Uraian</th>
            <th style="width: 14mm;">Manual</th>
            <th style="width: 17mm;">Nilai Akhir</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $idx => $item)
          <tr>
            <td class="text-center">{{ $idx + 1 }}</td>
            <td class="mono text-center">{{ $item->student_nis }}</td>
            <td class="font-bold">
              {{ $item->student_name }}
              @if($item->teacher_note)
                <div style="font-weight: 400; font-size: 7.3pt;">Catatan: {{ $item->teacher_note }}</div>
              @endif
            </td>
            <td class="text-center">{{ $item->student_kelas }}</td>
            <td class="text-center">{{ $item->student_ruang ?: '-' }}</td>
            <td class="text-center mono">{{ $item->answered_count }}/{{ $scoreArchive->total_questions }}</td>
            <td class="text-right mono">{{ (float) $item->objective_score }}</td>
            <td class="text-right mono">{{ (float) $item->essay_score }}</td>
            <td class="text-right mono">{{ $item->manual_score !== null ? (float) $item->manual_score : '-' }}</td>
            <td class="text-right mono font-bold">{{ (float) $item->final_score }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center" style="padding: 6mm;">Tidak ada data nilai siswa.</td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <div class="sign-grid">
        <div class="sign-block">
          <div>Pembuat Soal / Guru Mata Pelajaran,</div>
          <div class="sign-space"></div>
          <div class="sign-name">{{ $scoreArchive->teacher_name }}</div>
        </div>
        <div class="sign-block" style="padding-left: 20mm;">
          <div>Kepala Sekolah,</div>
          @if(!empty($settings['principal_name']) || !empty($settings['principal_nip']))
            <div class="sign-qr js-qr" data-qr="Ditandatangani oleh: {{ $settings['principal_name'] ?: '-' }}&#10;NIP: {{ $settings['principal_nip'] ?: '-' }}"></div>
          @else
            <div class="sign-space"></div>
          @endif
          <div class="sign-name">{{ $settings['principal_name'] ?: '____________________' }}</div>
          @if(!empty($settings['principal_nip']))
            <div class="mono" style="font-size: 8pt;">NIP. {{ $settings['principal_nip'] }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof qrcode === 'undefined') return;
      document.querySelectorAll('.js-qr[data-qr]').forEach(function (el) {
        try {
          var qr = qrcode(0, 'M');
          qr.addData(el.getAttribute('data-qr') || '');
          qr.make();
          el.innerHTML = qr.createSvgTag({ cellSize: 2, margin: 0, scalable: true });
        } catch (e) {}
      });
    });
  </script>
</body>
</html>
