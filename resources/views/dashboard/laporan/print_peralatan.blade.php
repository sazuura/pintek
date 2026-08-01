@extends('layouts.print')

@section('content')
    <table class="w-full border-collapse text-[11px]">
        <thead>
            <tr>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold w-[30px]">#</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Judul Rapat</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Peralatan</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Kode</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Lokasi</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Jumlah</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Peminjam</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Tgl Pinjam</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($peralatan as $p)
                @foreach($p->items as $item)
                    <tr class="even:bg-gray-50 align-top">
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $no++ }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $item->peralatan->nama_peralatan ?? '-' }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $item->peralatan->kode_barang ?? '-' }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $item->peralatan->gedung ?? '-' }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100 text-center">{{ $item->jumlah }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->user->nama_user ?? '-' }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</td>
                        <td class="py-1.5 px-2.5 border-b border-gray-100 text-center"><x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge></td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-gray-500">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        lpBuatDanUnduhPdf(@json($pdfHeaders), @json($pdfRows), @json($namaFile), {{ $pdfStatusIndex }});
    </script>
@endsection
