<table class="info-table">
    <tr><td class="label">Pemohon</td><td class="value">{{ $namaOperator }}</td></tr>
    <tr><td class="label">Gedung</td><td class="value">{{ $gedung }}</td></tr>
    <tr><td class="label">Tanggal</td><td class="value">{{ $tanggalPinjam }} - {{ $tanggalKembali }}</td></tr>
    <tr><td class="label">Keperluan</td><td class="value">{{ $keperluan }}</td></tr>
    @if($terkaitJadwal)
    <tr><td class="label">Terkait Rapat</td><td class="value">{{ $terkaitJadwal }}</td></tr>
    @endif
</table>
<p style="margin: 0 0 8px; font-size: 13px; font-weight: 600; color: #646464;">Peralatan yang Dipinjam</p>
@foreach($peralatanPerGedung as $gedungNama => $items)
    @if(count($peralatanPerGedung) > 1)
    <p style="margin: 0 0 4px; font-size: 13px; font-weight: 600; color: #646464;">{{ $gedungNama }}</p>
    @endif
    <table class="info-table" style="margin-bottom: {{ $loop->last ? '16px' : '12px' }};">
        @foreach($items as $alat)
        <tr><td class="value" style="width: auto;">{{ $alat['nama'] }}</td><td class="value" style="text-align: right; color: #646464;">x{{ $alat['jumlah'] }}</td></tr>
        @endforeach
    </table>
@endforeach
