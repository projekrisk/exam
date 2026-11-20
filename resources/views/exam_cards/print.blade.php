<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Kartu Ujian | {{ $settings['school_name'] }}</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Bricolage Grotesque", sans-serif;
      color: #1f2937;
      background: #f3f4f6;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

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

    .toolbar-info {
      font-size: 14px;
      color: #374151;
    }

    .toolbar-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

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
      color: #222222;
    }

    .btn-primary {
      background: #2563eb;
      border-color: #2563eb;
      color: #ffffff;
    }

    .pages-wrap {
      padding: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }

    .sheet {
      width: 210mm;
      height: 297mm;
      padding: 6mm 7.5mm;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      grid-template-rows: repeat(4, 1fr);
      gap: 3mm 4mm;
      page-break-after: always;
    }

    .sheet:last-child {
      page-break-after: auto;
    }

    .exam-card {
      border: 1.2px solid #000000;
      border-radius: 3px;
      padding: 2mm 2.8mm;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: #ffffff;
      color: #000000;
      overflow: hidden;
    }

    .card-kop {
      display: flex;
      align-items: center;
      gap: 2.5mm;
      padding-bottom: 1mm;
      border-bottom: 1.5px solid #000000;
    }

    .kop-logo {
      width: 10mm;
      height: 10mm;
      object-fit: contain;
      flex-shrink: 0;
    }

    .kop-text {
      flex: 1;
      min-width: 0;
      text-align: left;
      line-height: 1.15;
    }

    .kop-prov {
      font-size: 7.5pt;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.2px;
      color: #000000;
    }

    .kop-dinas {
      font-size: 7.8pt;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.15px;
      color: #000000;
    }

    .kop-school {
      font-size: 10pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.15px;
      color: #000000;
      margin-top: 0.2mm;
    }

    .card-heading {
      text-align: center;
      padding: 0.5mm 0 0.2mm;
      margin-top: 0.3mm;
      line-height: 1.12;
    }

    .card-title {
      font-size: 9.2pt;
      font-weight: 800;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      color: #000000;
    }

    .card-activity {
      font-size: 8pt;
      font-weight: 700;
      text-transform: uppercase;
      color: #000000;
    }

    .card-year {
      font-size: 7.2pt;
      font-weight: 600;
      text-transform: uppercase;
      color: #000000;
    }

    .card-data {
      display: flex;
      flex-direction: column;
      gap: 0.8mm;
      padding: 0.6mm 0;
    }

    .data-name-row {
      display: flex;
      align-items: baseline;
      font-size: 8.8pt;
      padding-bottom: 0.8mm;
      border-bottom: 0.6px solid #e5e7eb;
    }

    .data-name-row .lbl {
      font-weight: 400;
      color: #000000;
      width: 11.5mm;
      flex-shrink: 0;
    }

    .data-name-row .sep {
      font-weight: 400;
      color: #000000;
      width: 2.5mm;
      text-align: center;
      flex-shrink: 0;
    }

    .data-name-row .val {
      font-weight: 700;
      color: #000000;
      flex: 1;
      min-width: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .data-cols {
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      gap: 2.5mm;
      align-items: stretch;
      font-size: 8pt;
      line-height: 1.25;
    }

    .placement-box {
      border: 0.9px solid #000000;
      border-radius: 2px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      overflow: hidden;
      align-self: center;
    }

    .placement-cell {
      padding: 1mm 1.4mm;
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .placement-cell + .placement-cell {
      border-left: 0.8px solid #000000;
      background: #f3f4f6;
    }

    .placement-lbl {
      font-size: 6.2pt;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      color: #374151;
    }

    .placement-val {
      font-size: 8.5pt;
      font-weight: 800;
      color: #000000;
      margin-top: 0.2mm;
    }

    .meta-table {
      width: 100%;
      border-collapse: collapse;
    }

    .meta-table td {
      padding: 0.18mm 0;
      vertical-align: top;
    }

    .meta-table td.lbl {
      width: 11.5mm;
      color: #000000;
      font-weight: 400;
      white-space: nowrap;
    }

    .meta-table td.sep {
      width: 2.5mm;
      text-align: center;
      color: #000000;
      font-weight: 400;
    }

    .meta-table td.val {
      font-weight: 700;
      color: #000000;
      word-break: break-word;
    }

    .card-footer {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 3mm;
      padding-top: 0.8mm;
      border-top: 0.6px solid #d1d5db;
    }

    .footer-note {
      flex: 1;
      font-size: 6.6pt;
      color: #000000;
      line-height: 1.22;
    }

    .footer-note strong {
      color: #000000;
      display: block;
      margin-bottom: 0.15mm;
    }

    .sign-box {
      text-align: left;
      min-width: 38mm;
      font-size: 7pt;
      line-height: 1.12;
      color: #000000;
      flex-shrink: 0;
    }

    .sign-role {
      color: #000000;
      font-weight: 600;
    }

    .sign-barcode {
      width: 8mm;
      height: 8mm;
      margin: 0.4mm 0;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .sign-barcode svg {
      width: 100%;
      height: 100%;
      display: block;
    }

    .sign-name {
      font-weight: 700;
      color: #000000;
      text-decoration: underline;
    }

    .sign-nip {
      font-size: 6.5pt;
      color: #000000;
    }

    .empty-state {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 36px;
      text-align: center;
      color: #4b5563;
      font-size: 14px;
      margin-top: 24px;
    }

    @page {
      size: A4 portrait;
      margin: 0;
    }

    @media print {
      body {
        background: #ffffff;
      }

      .no-print {
        display: none !important;
      }

      .pages-wrap {
        padding: 0;
        gap: 0;
      }

      .sheet {
        box-shadow: none;
        margin: 0;
      }
    }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <div class="toolbar-info">
      <strong>Kartu Ujian</strong> · Total <strong>{{ $students->count() }}</strong> siswa ({{ max(1, ceil($students->count() / 8)) }} halaman A4 · 8 kartu/halaman)
    </div>
    <div class="toolbar-actions">
      <a href="{{ route('exam-cards.index') }}" class="btn">Kembali</a>
      @if($students->isNotEmpty())
        <button type="button" onclick="window.print()" class="btn btn-primary">Cetak Kartu (Ctrl+P)</button>
      @endif
    </div>
  </div>

  @if($students->isEmpty())
    <div class="pages-wrap">
      <div class="empty-state">
        Tidak ada data siswa yang ditemukan untuk dicetak.
      </div>
    </div>
  @else
    <div class="pages-wrap">
      @foreach($students->chunk(8) as $pageStudents)
        <section class="sheet">
          @foreach($pageStudents as $student)
            <article class="exam-card">
              <div>
                <header class="card-kop">
                  @if(!empty($settings['school_logo_path']))
                    <img src="{{ asset('uploads/soal/' . $settings['school_logo_path']) }}" alt="Logo" class="kop-logo">
                  @endif
                  <div class="kop-text">
                    <div class="kop-prov">Pemerintah Provinsi Banten</div>
                    <div class="kop-dinas">Dinas Pendidikan dan Kebudayaan</div>
                    <div class="kop-school">{{ $settings['school_name'] ?: 'SMAN 1 Malingping' }}</div>
                  </div>
                </header>

                <div class="card-heading">
                  <div class="card-title">Kartu Peserta Ujian</div>
                  <div class="card-activity">{{ $settings['activity_name'] ?: 'Penilaian Sumatif Akhir Semester' }}</div>
                  <div class="card-year">Tahun Ajaran {{ $settings['academic_year'] ?: '2026/2027' }}</div>
                </div>
              </div>

              <div class="card-data">
                <div class="data-name-row">
                  <span class="lbl">Nama</span>
                  <span class="sep">:</span>
                  <span class="val">{{ $student->name }}</span>
                </div>

                <div class="data-cols">
                  <table class="meta-table">
                    <tr>
                      <td class="lbl">NIS</td>
                      <td class="sep">:</td>
                      <td class="val mono">{{ $student->nis }}</td>
                    </tr>
                    <tr>
                      <td class="lbl">NISN</td>
                      <td class="sep">:</td>
                      <td class="val mono">{{ $student->nisn }}</td>
                    </tr>
                    <tr>
                      <td class="lbl">Kelas</td>
                      <td class="sep">:</td>
                      <td class="val">{{ $student->kelas }}</td>
                    </tr>
                  </table>

                  <div class="placement-box">
                    <div class="placement-cell">
                      <div class="placement-lbl">Ruang</div>
                      <div class="placement-val">{{ $student->ruang ?: '-' }}</div>
                    </div>
                    <div class="placement-cell">
                      <div class="placement-lbl">No. Meja</div>
                      <div class="placement-val mono">{{ $student->seat_number ? str_pad((string) $student->seat_number, 2, '0', STR_PAD_LEFT) : '-' }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <footer class="card-footer">
                <div class="footer-note">
                  <strong>Catatan:</strong>
                  Kartu ini merupakan tanda peserta resmi dan wajib dibawa pada setiap sesi pelaksanaan ujian.
                </div>

                <div class="sign-box">
                  <div class="sign-role">Kepala Sekolah,</div>
                  <div class="sign-barcode js-qr" data-qr="{{ $principalQrPayload }}" title="Tanda Tangan Digital Kepala Sekolah"></div>
                  <div class="sign-name">{{ $settings['principal_name'] ?: '____________________' }}</div>
                  @if(!empty($settings['principal_nip']))
                    <div class="sign-nip mono">NIP. {{ $settings['principal_nip'] }}</div>
                  @endif
                </div>
              </footer>
            </article>
          @endforeach
        </section>
      @endforeach
    </div>
  @endif

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof qrcode === 'undefined') return;
      document.querySelectorAll('.js-qr[data-qr]').forEach(function (el) {
        try {
          var qr = qrcode(0, 'M');
          qr.addData(el.getAttribute('data-qr') || '');
          qr.make();
          el.innerHTML = qr.createSvgTag({ cellSize: 2, margin: 0, scalable: true });
        } catch (e) {
          el.textContent = 'QR';
        }
      });
    });
  </script>
</body>
</html>
