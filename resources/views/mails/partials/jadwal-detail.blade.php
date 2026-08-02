<table class="info-table">
    <tr><td class="label">Tanggal</td><td class="value">{{ $tanggal }}</td></tr>
    <tr><td class="label">Waktu</td><td class="value">{{ $waktuMulai }} - {{ $waktuSelesai }} WIB</td></tr>
    <tr><td class="label">Platform</td><td class="value">{{ $platform }}</td></tr>
    @if($lokasiFisik)
    <tr><td class="label">Lokasi</td><td class="value">{{ $lokasiFisik }}</td></tr>
    @endif
    <tr><td class="label">Keterangan</td><td class="value">{{ $keterangan }}</td></tr>
    @if($zoomPassword)
    <tr><td class="label">Password Zoom</td><td class="value">{{ $zoomPassword }}</td></tr>
    @endif
</table>
@if(!empty($daftarPeralatan))
<p style="margin: 0 0 8px; font-size: 13px; font-weight: 600; color: #646464;">Peralatan yang Harus Dipinjam</p>
<table class="info-table">
    @foreach($daftarPeralatan as $alat)
    <tr><td class="value" style="width: auto;">{{ $alat['nama'] }}</td><td class="value" style="text-align: right; color: #646464;">x{{ $alat['jumlah'] }}</td></tr>
    @endforeach
</table>
@endif
