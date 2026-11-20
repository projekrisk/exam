<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Data Guru Mapel | {{ $settings['school_name'] }}</title>
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
      font-size: 9.5pt;
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

    .doc-heading { text-align: center; padding: 4mm 0 3.5mm; }
    .doc-title { font-size: 12pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px; }
    .doc-sub { font-size: 9.2pt; font-weight: 600; margin-top: 0.6mm; }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8.8pt;
    }
    .data-table thead { display: table-header-group; }
    .data-table tr { break-inside: avoid; }
    .data-table th, .data-table td {
      border: 0.75px solid #000000;
      padding: 1.6mm 2mm;
      vertical-align: top;
    }
    .data-table th {
      background: #f3f4f6;
      font-weight: 700;
      text-align: center;
    }
    .text-center { text-align: center; }
    .font-bold { font-weight: 700; }
    .mapel-item + .mapel-item {
      margin-top: 1.2mm;
      padding-top: 1.2mm;
      border-top: 0.5px dashed #9ca3af;
    }

    .sign-wrap {
      display: flex;
      justify-content: flex-end;
      margin-top: 8mm;
      break-inside: avoid;
      font-size: 9pt;
    }
    .sign-block { min-width: 58mm; line-height: 1.3; }
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
      <strong>Pratinjau Cetak Data Guru Mapel</strong> · Total <strong>{{ $teachers->count() }}</strong> guru
    </div>
    <div class="toolbar-actions">
      <a href="{{ route('teachers.index') }}" class="btn">Kembali</a>
      <button type="button" onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF (Ctrl+P)</button>
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
        <div class="doc-title">Daftar Guru Mata Pelajaran</div>
        <div class="doc-sub">{{ $settings['activity_name'] ?: 'Penilaian Sumatif Akhir Semester' }} · Tahun Ajaran {{ $settings['academic_year'] ?: '2026/2027' }}</div>
      </div>

      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 9mm;">No</th>
            <th style="width: 34mm;">NIP</th>
            <th style="width: 48mm;">Nama Lengkap</th>
            <th style="width: 38mm;">Mata Pelajaran</th>
            <th>Kelas yang Diampu</th>
          </tr>
        </thead>
        <tbody>
          @forelse($teachers as $idx => $teacher)
          <tr>
            <td class="text-center">{{ $idx + 1 }}</td>
            <td class="mono">{{ $teacher->nip ?? $teacher->username }}</td>
            <td class="font-bold">{{ $teacher->name }}</td>
            <td>
              @forelse($teacher->binding_groups as $bg)
                <div class="mapel-item">{{ $bg['subject_name'] }}</div>
              @empty
                <span>-</span>
              @endforelse
            </td>
            <td>
              @forelse($teacher->binding_groups as $bg)
                <div class="mapel-item">{{ !empty($bg['classes']) ? implode(', ', $bg['classes']) : '-' }}</div>
              @empty
                <span>-</span>
              @endforelse
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center" style="padding: 6mm;">Tidak ada data guru mata pelajaran.</td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <div class="sign-wrap">
        <div class="sign-block">
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
