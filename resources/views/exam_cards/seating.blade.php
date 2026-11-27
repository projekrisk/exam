<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Denah Tempat Duduk | {{ $settings['school_name'] }}</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Bricolage Grotesque", sans-serif;
      color: #000000;
      background: #f3f4f6;
      font-size: 9pt;
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
      flex-wrap: wrap;
      align-items: flex-end;
      justify-content: space-between;
      gap: 10px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .toolbar-form {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      gap: 8px;
    }
    .field { display: flex; flex-direction: column; gap: 3px; }
    .field label { font-size: 11px; font-weight: 600; color: #4b5563; }
    .field input, .field select {
      border: 1px solid #d1d5db;
      border-radius: 6px;
      padding: 6px 8px;
      font-size: 12.5px;
      font-family: inherit;
      background: #ffffff;
    }
    .toolbar-actions { display: flex; align-items: center; gap: 8px; }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 13px;
      border-radius: 6px;
      font-size: 12.5px;
      font-weight: 600;
      font-family: inherit;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid #d1d5db;
      background: #ffffff;
      color: #111827;
    }
    .btn-primary { background: #2563eb; border-color: #2563eb; color: #ffffff; }

    .pages-wrap { padding: 18px; display: flex; flex-direction: column; align-items: center; gap: 18px; }
    .sheet {
      width: 210mm;
      min-height: 297mm;
      padding: 8mm 9mm;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      page-break-after: always;
    }
    .plan-sheet {
      height: 297mm;
      display: flex;
      flex-direction: column;
    }
    .sheet:last-child { page-break-after: auto; }

    .kop {
      display: flex;
      align-items: center;
      gap: 3.5mm;
      padding-bottom: 2.2mm;
      border-bottom: 2px solid #000000;
    }
    .kop-logo { width: 13mm; height: 13mm; object-fit: contain; flex-shrink: 0; }
    .kop-text { flex: 1; text-align: left; line-height: 1.15; }
    .kop-prov { font-size: 8.5pt; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
    .kop-dinas { font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
    .kop-school { font-size: 12pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px; }
    .kop-right { text-align: right; line-height: 1.25; }
    .room-badge { font-size: 13pt; font-weight: 800; text-transform: uppercase; }
    .room-meta { font-size: 8pt; }

    .plan-title {
      text-align: center;
      margin: 2.5mm 0 2mm;
      line-height: 1.2;
    }
    .plan-title-main {
      font-size: 11pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }
    .plan-title-activity {
      font-size: 9.5pt;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .plan-title-year {
      font-size: 8.5pt;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .room-layout {
      border: 1.6px solid #000000;
      padding: 3.5mm 4mm 4mm;
      position: relative;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .front-stage {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 4mm;
      margin-bottom: 3.5mm;
      padding-bottom: 2.5mm;
      border-bottom: 1px dashed #9ca3af;
    }
    .front-bar {
      min-width: 62mm;
      border: 1.4px solid #000000;
      background: #f9fafb;
      text-align: center;
      padding: 1.5mm 3mm;
      font-size: 8pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .door-wrap {
      display: flex;
      align-items: center;
      gap: 2mm;
      font-size: 7.5pt;
      font-weight: 700;
    }
    .door-wrap.left { justify-content: flex-start; }
    .door-wrap.right { justify-content: flex-end; }
    .door-badge {
      display: inline-flex;
      flex-direction: column;
      line-height: 1.15;
    }
    .door-title {
      font-size: 7.5pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .door-hint {
      font-size: 7pt;
      font-weight: 600;
      color: #374151;
    }

    .seat-grid {
      display: grid;
      gap: 2.5mm;
      flex: 1;
    }
    .seat-cell {
      border: 1.1px solid #000000;
      border-radius: 3px;
      padding: 2mm 1.8mm;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      text-align: center;
      break-inside: avoid;
    }
    .seat-cell.empty {
      border: 0.75px dashed #9ca3af;
      color: #6b7280;
    }
    .seat-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1mm;
      font-size: 8pt;
      border-bottom: 0.7px solid #d1d5db;
      padding-bottom: 0.8mm;
    }
    .seat-no { font-weight: 800; }
    .seat-kelas { font-weight: 700; font-size: 7.8pt; }
    .seat-urut {
      font-size: 18pt;
      font-weight: 800;
      line-height: 1.1;
      margin: auto 0;
    }
    .seat-urut-lbl { font-size: 7pt; color: #4b5563; font-weight: 600; }

    /* Kotak Nama Bangku (Gunting & Tempel) — muat 1 halaman */
    .label-sheet-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1.4px solid #000000;
      padding-bottom: 1.5mm;
      margin-bottom: 2mm;
    }
    .label-sheet-title {
      font-size: 9.5pt;
      font-weight: 800;
      text-transform: uppercase;
    }
    .label-sheet-sub {
      font-size: 7.2pt;
      color: #374151;
    }
    .label-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1.6mm;
    }
    .desk-label {
      border: 1px dashed #111827;
      padding: 1.2mm 1.6mm;
      min-height: 20mm;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      break-inside: avoid;
      page-break-inside: avoid;
      background: #ffffff;
    }
    .desk-label-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.2mm;
      border-bottom: 0.8px solid #000000;
      padding-bottom: 0.6mm;
    }
    .desk-room {
      font-size: 6.5pt;
      font-weight: 700;
      text-transform: uppercase;
    }
    .desk-seat {
      font-size: 7.5pt;
      font-weight: 800;
      border: 0.8px solid #000000;
      padding: 0.2mm 1.2mm;
      background: #f3f4f6;
    }
    .desk-name {
      font-size: 7.5pt;
      font-weight: 800;
      line-height: 1.12;
      margin: 0.8mm 0 0.6mm;
      word-break: break-word;
    }
    .desk-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 6.5pt;
      color: #111827;
    }

    @page { size: A4 portrait; margin: 8mm; }
    @media print {
      body { background: #ffffff; }
      .no-print { display: none !important; }
      .pages-wrap { padding: 0; gap: 0; }
      .sheet { width: 100%; min-height: auto; padding: 0; box-shadow: none; }
      .plan-sheet { height: 280mm; min-height: 280mm; }
    }
  </style>
</head>
<body>
  @php
    $patternLabels = [
      'left_to_right' => 'Kiri ke Kanan',
      'right_to_left' => 'Kanan ke Kiri',
      'front_to_back' => 'Depan ke Belakang',
      'zigzag' => 'Zigzag (Ular)',
    ];
    $doorLabels = [
      'auto' => 'Otomatis (Ikuti Awal Meja)',
      'left' => 'Kiri Depan',
      'right' => 'Kanan Depan',
    ];
    $viewModeLabels = [
      'both' => 'Denah + Label Meja',
      'plan' => 'Denah Saja',
      'labels' => 'Label Meja Saja',
    ];
    $schoolNameUpper = strtoupper($settings['school_name'] ?: 'SMAN 1 MALINGPING');
    $activityNameUpper = strtoupper($settings['activity_name'] ?: 'KEGIATAN UJIAN');
  @endphp

  <div class="toolbar no-print">
    <form method="GET" action="{{ route('exam-cards.seating-plan') }}" class="toolbar-form">
      <input type="hidden" name="save" value="1">
      <div class="field">
        <label>Ruang</label>
        <select name="ruang" onchange="this.form.submit()">
          <option value="all" {{ $selectedRoom === 'all' ? 'selected' : '' }}>Semua Ruang ({{ $rooms->count() }})</option>
          @foreach($rooms as $rm)
            <option value="{{ $rm }}" {{ $selectedRoom === $rm ? 'selected' : '' }}>{{ $rm }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Kursi</label>
        <input type="number" name="seat_count" value="{{ $seatCount }}" min="1" max="200" style="width: 66px;">
      </div>
      <div class="field">
        <label>Baris</label>
        <input type="number" name="rows" value="{{ $rows }}" min="1" max="30" style="width: 60px;">
      </div>
      <div class="field">
        <label>Kolom</label>
        <input type="number" name="cols" value="{{ $cols }}" min="1" max="20" style="width: 60px;">
      </div>
      <div class="field">
        <label>Model Urutan</label>
        <select name="pattern">
          @foreach($patternLabels as $pKey => $pLbl)
            <option value="{{ $pKey }}" {{ $pattern === $pKey ? 'selected' : '' }}>{{ $pLbl }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Posisi Pintu</label>
        <select name="door_side">
          @foreach($doorLabels as $dKey => $dLbl)
            <option value="{{ $dKey }}" {{ $doorSide === $dKey ? 'selected' : '' }}>{{ $dLbl }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Tampilan Cetak</label>
        <select name="view_mode">
          @foreach($viewModeLabels as $vKey => $vLbl)
            <option value="{{ $vKey }}" {{ $viewMode === $vKey ? 'selected' : '' }}>{{ $vLbl }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn">Terapkan Denah</button>
    </form>

    <div class="toolbar-actions">
      <a href="{{ route('exam-cards.index') }}" class="btn">Kembali</a>
      @if(!empty($roomPlans))
        <button type="button" onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF (Ctrl+P)</button>
      @endif
    </div>
  </div>

  @if(empty($roomPlans))
    <div class="pages-wrap">
      <div class="sheet" style="display:flex;align-items:center;justify-content:center;">
        <p style="font-size:12pt;">Belum ada data ruang peserta. Silakan jalankan <strong>Generate Ruang</strong> di halaman Kartu Ujian terlebih dahulu.</p>
      </div>
    </div>
  @else
    <div class="pages-wrap">
      @foreach($roomPlans as $plan)
        @if($viewMode !== 'labels')
          <section class="sheet plan-sheet">
            <header class="kop">
              @if(!empty($settings['school_logo_path']))
                <img src="{{ asset('uploads/soal/' . $settings['school_logo_path']) }}" alt="Logo" class="kop-logo">
              @endif
              <div class="kop-text">
                <div class="kop-prov">PEMERINTAH PROVINSI BANTEN</div>
                <div class="kop-dinas">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                <div class="kop-school">{{ $schoolNameUpper }}</div>
              </div>
              <div class="kop-right">
                <div class="room-badge">{{ $plan['room_name'] }}</div>
                <div class="room-meta">{{ $plan['student_count'] }} Peserta · Urutan: {{ $patternLabels[$pattern] ?? $pattern }}</div>
              </div>
            </header>

            <div class="plan-title">
              <div class="plan-title-main">DENAH TEMPAT DUDUK</div>
              <div class="plan-title-activity">{{ $activityNameUpper }}</div>
              <div class="plan-title-year">TAHUN AJARAN {{ strtoupper($settings['academic_year'] ?: '2026/2027') }}</div>
            </div>

            <div class="room-layout">
              <div class="front-stage">
                <div>
                  @if($effectiveDoorSide === 'left')
                    <div class="door-wrap left">
                      <svg width="34" height="30" viewBox="0 0 34 30" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Pintu Kelas">
                        <line x1="2" y1="2" x2="2" y2="28" stroke="#000" stroke-width="2.5"/>
                        <line x1="2" y1="2" x2="26" y2="2" stroke="#000" stroke-width="2"/>
                        <path d="M26 2 A24 24 0 0 1 2 26" stroke="#000" stroke-width="1.2" stroke-dasharray="3 2" fill="none"/>
                      </svg>
                      <div class="door-badge">
                        <span class="door-title">PINTU MASUK</span>
                        <span class="door-hint">Mulai Urutan Meja 01 &rarr;</span>
                      </div>
                    </div>
                  @endif
                </div>

                <div class="front-bar">Papan Tulis / Meja Pengawas</div>

                <div>
                  @if($effectiveDoorSide === 'right')
                    <div class="door-wrap right">
                      <div class="door-badge" style="text-align:right;">
                        <span class="door-title">PINTU MASUK</span>
                        <span class="door-hint">&larr; Mulai Urutan Meja 01</span>
                      </div>
                      <svg width="34" height="30" viewBox="0 0 34 30" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Pintu Kelas">
                        <line x1="32" y1="2" x2="32" y2="28" stroke="#000" stroke-width="2.5"/>
                        <line x1="32" y1="2" x2="8" y2="2" stroke="#000" stroke-width="2"/>
                        <path d="M8 2 A24 24 0 0 0 32 26" stroke="#000" stroke-width="1.2" stroke-dasharray="3 2" fill="none"/>
                      </svg>
                    </div>
                  @endif
                </div>
              </div>

              <div class="seat-grid" style="grid-template-columns: repeat({{ $plan['cols'] ?? $cols }}, minmax(0, 1fr)); grid-template-rows: repeat({{ max(1, count($plan['grid'])) }}, minmax(0, 1fr));">
                @foreach($plan['grid'] as $rowCells)
                  @foreach($rowCells as $cell)
                    @if($cell['student'])
                      <div class="seat-cell">
                        <div class="seat-top">
                          <span class="seat-no mono">M{{ str_pad((string) $cell['seat_no'], 2, '0', STR_PAD_LEFT) }}</span>
                          <span class="seat-kelas">{{ $cell['student']->kelas }}</span>
                        </div>
                        <div class="seat-urut mono">{{ str_pad((string) ($cell['attendance_no'] ?? $cell['seat_no']), 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="seat-urut-lbl mono">{{ $cell['student']->nis }}</div>
                      </div>
                    @else
                      <div aria-hidden="true"></div>
                    @endif
                  @endforeach
                @endforeach
              </div>
            </div>
          </section>
        @endif

        @if($viewMode !== 'plan' && !empty($plan['labels']))
          <section class="sheet label-sheet">
            <div class="label-sheet-header">
              <div>
                <div class="label-sheet-title">LABEL NAMA MEJA PESERTA — {{ $plan['room_name'] }}</div>
                <div class="label-sheet-sub">{{ $activityNameUpper }} · {{ $schoolNameUpper }} · Gunting mengikuti garis putus-putus &amp; tempel di meja peserta</div>
              </div>
              <div class="room-meta mono">{{ count($plan['labels']) }} Label</div>
            </div>

            <div class="label-grid">
              @foreach($plan['labels'] as $lbl)
                <div class="desk-label">
                  <div class="desk-label-top">
                    <span class="desk-room">{{ $plan['room_name'] }}</span>
                    <span class="desk-seat mono">MEJA {{ str_pad((string) $lbl['seat_no'], 2, '0', STR_PAD_LEFT) }}</span>
                  </div>
                  <div class="desk-name">{{ $lbl['student']->name }}</div>
                  <div class="desk-meta">
                    <span><strong>{{ $lbl['student']->kelas }}</strong></span>
                    <span class="mono">{{ $lbl['student']->nis }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          </section>
        @endif
      @endforeach
    </div>
  @endif
</body>
</html>
