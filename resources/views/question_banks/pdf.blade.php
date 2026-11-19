<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Naskah Soal PDF | {{ $questionBank->title }}</title>
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
      margin-bottom: 5mm;
      padding-bottom: 3mm;
      border-bottom: 0.75px solid #000000;
      font-size: 8.8pt;
    }
    .meta-table { width: 100%; border-collapse: collapse; }
    .meta-table td { padding: 0.5mm 0; vertical-align: top; }
    .meta-table td.lbl { width: 28mm; }
    .meta-table td.sep { width: 3mm; text-align: center; }
    .meta-table td.val { font-weight: 700; }

    .questions-list { display: flex; flex-direction: column; gap: 4mm; }
    .q-item { break-inside: avoid; font-size: 9.6pt; line-height: 1.45; }
    .q-row { display: flex; align-items: flex-start; gap: 2mm; }
    .q-num { font-weight: 700; min-width: 6mm; flex-shrink: 0; }
    .q-body { flex: 1; min-width: 0; }
    .q-text { white-space: pre-line; }
    .q-img {
      margin-top: 2mm;
      max-width: 85mm;
      max-height: 55mm;
      object-fit: contain;
      display: block;
      border: 0.5px solid #cbd5e1;
      border-radius: 3px;
    }

    .opt-list { margin-top: 1.5mm; display: flex; flex-direction: column; gap: 1mm; }
    .opt-row { display: flex; align-items: flex-start; gap: 2mm; }
    .opt-key { font-weight: 600; min-width: 5.5mm; flex-shrink: 0; }
    .opt-text { flex: 1; min-width: 0; }
    .opt-img {
      margin-top: 1mm;
      max-width: 55mm;
      max-height: 35mm;
      object-fit: contain;
      display: block;
    }

    .tf-row {
      margin-top: 1.8mm;
      display: flex;
      gap: 8mm;
      font-size: 9.2pt;
    }
    .tf-box {
      display: inline-flex;
      align-items: center;
      gap: 2mm;
    }
    .checkbox-square {
      display: inline-block;
      width: 3.4mm;
      height: 3.4mm;
      border: 0.9px solid #000000;
      border-radius: 0.6mm;
    }

    .essay-lines { margin-top: 2mm; display: flex; flex-direction: column; gap: 4.5mm; }
    .essay-line { border-bottom: 0.6px dotted #000000; height: 4mm; }

    .answer-page { page-break-before: always; padding-top: 2mm; }
    .key-title { font-size: 11.5pt; font-weight: 800; text-transform: uppercase; margin-bottom: 3mm; }
    .key-table { width: 100%; border-collapse: collapse; font-size: 8.8pt; }
    .key-table thead { display: table-header-group; }
    .key-table tr { break-inside: avoid; }
    .key-table th, .key-table td {
      border: 0.75px solid #000000;
      padding: 1.5mm 2mm;
      vertical-align: top;
    }
    .key-table th { background: #f3f4f6; font-weight: 700; text-align: center; }
    .text-center { text-align: center; }
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
  @php
    $targets = collect($questionBank->target_classes ?? ['ALL'])
      ->sort(SORT_NATURAL | SORT_FLAG_CASE)
      ->map(fn ($c) => $c === 'ALL' ? 'Semua Kelas' : $c)
      ->implode(', ');
  @endphp

  <div class="toolbar no-print">
    <div class="toolbar-info">
      <strong>Pratinjau PDF Naskah Soal</strong> · {{ $questionBank->title }} ({{ $questions->count() }} butir soal)
    </div>
    <div class="toolbar-actions">
      <a href="{{ route('question-banks.show', $questionBank) }}" class="btn">Kembali</a>
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
        <div class="doc-title">Naskah Soal Ujian</div>
        <div class="doc-sub">{{ $settings['activity_name'] ?: 'Penilaian Sumatif Akhir Semester' }} · Tahun Ajaran {{ $settings['academic_year'] ?: '2026/2027' }}</div>
      </div>

      <div class="meta-grid">
        <table class="meta-table">
          <tr><td class="lbl">Paket Soal</td><td class="sep">:</td><td class="val">{{ $questionBank->title }}</td></tr>
          <tr><td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td class="val">{{ $questionBank->subject->name ?? '-' }}</td></tr>
        </table>
        <table class="meta-table">
          <tr><td class="lbl">Pembuat Soal</td><td class="sep">:</td><td class="val">{{ $questionBank->teacher->name ?? '-' }}</td></tr>
          <tr><td class="lbl">Target Kelas</td><td class="sep">:</td><td class="val">{{ $targets }} ({{ $questions->count() }} Soal)</td></tr>
        </table>
      </div>

      <div class="questions-list">
        @forelse($questions as $idx => $q)
          <div class="q-item">
            <div class="q-row">
              <div class="q-num">{{ $idx + 1 }}.</div>
              <div class="q-body">
                <div class="q-text">{{ $q->question_text }}</div>
                @if(!empty($q->media_path))
                  <img src="{{ asset('uploads/soal/' . $q->media_path) }}" alt="Gambar Soal {{ $idx + 1 }}" class="q-img">
                @endif

                @if(in_array($q->type, ['pg', 'pg_kompleks'], true))
                  <div class="opt-list">
                    @foreach(($q->options ?? []) as $opt)
                      <div class="opt-row">
                        <div class="opt-key">{{ $opt['key'] ?? '' }}.</div>
                        <div class="opt-text">
                          <div>{{ $opt['text'] ?? '' }}</div>
                          @if(!empty($opt['image']))
                            <img src="{{ asset('uploads/soal/' . $opt['image']) }}" alt="Gambar Opsi {{ $opt['key'] ?? '' }}" class="opt-img">
                          @endif
                        </div>
                      </div>
                    @endforeach
                  </div>
                @elseif($q->type === 'benar_salah')
                  <div class="tf-row">
                    <span class="tf-box"><span class="checkbox-square"></span> Benar</span>
                    <span class="tf-box"><span class="checkbox-square"></span> Salah</span>
                  </div>
                @elseif($q->type === 'uraian')
                  <div class="essay-lines">
                    <div class="essay-line"></div>
                    <div class="essay-line"></div>
                    <div class="essay-line"></div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div style="text-align: center; padding: 12mm 0;">Belum ada butir soal dalam bank soal ini.</div>
        @endforelse
      </div>

      @if($questions->isNotEmpty())
      <div class="answer-page">
        <div class="key-title">Kunci Jawaban &amp; Pembobotan — {{ $questionBank->title }}</div>
        <table class="key-table">
          <thead>
            <tr>
              <th style="width: 10mm;">No</th>
              <th style="width: 34mm;">Tipe Soal</th>
              <th>Kunci Jawaban / Pedoman</th>
              <th style="width: 18mm;">Bobot</th>
            </tr>
          </thead>
          <tbody>
            @foreach($questions as $idx => $q)
              @php
                $typeLabel = match ($q->type) {
                  'pg' => 'Pilihan Ganda',
                  'pg_kompleks' => 'PG Kompleks',
                  'benar_salah' => 'Benar / Salah',
                  'uraian' => 'Uraian',
                  default => strtoupper((string) $q->type),
                };
                if (in_array($q->type, ['pg', 'pg_kompleks'], true)) {
                  $keyText = collect($q->options ?? [])->filter(fn ($o) => !empty($o['is_correct']))->pluck('key')->implode(', ') ?: '-';
                } elseif ($q->type === 'benar_salah') {
                  $val = $q->options['correct'] ?? null;
                  $keyText = ($val === true || $val === 'true' || $val === 1 || $val === '1') ? 'Benar' : 'Salah';
                } elseif ($q->type === 'uraian') {
                  $keyText = trim((string) ($q->options['expected_answer'] ?? '')) ?: 'Koreksi Manual';
                } else {
                  $keyText = '-';
                }
              @endphp
              <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $typeLabel }}</td>
                <td class="font-bold">{{ $keyText }}</td>
                <td class="text-center mono">{{ (float) $q->weight }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="sign-grid">
          <div class="sign-block">
            <div>Pembuat Soal / Guru Mata Pelajaran,</div>
            <div class="sign-space"></div>
            <div class="sign-name">{{ $questionBank->teacher->name ?? '-' }}</div>
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
      @endif
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
