@extends('layouts.print')

@section('content')
    <table class="w-full border-collapse text-[11px]">
        <thead>
            <tr>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold w-[30px]">#</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Judul Rapat</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Peralatan</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Peminjam</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Tgl Pinjam</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($peralatan as $index => $p)
                <tr class="even:bg-gray-50 align-top">
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $index + 1 }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">
                        @foreach($p->items as $item)
                            <div class="{{ !$loop->last ? 'mb-1' : '' }}">
                                {{ $item->peralatan->nama_peralatan ?? '-' }}
                                <span class="text-gray-500">({{ $item->peralatan->kode_barang ?? '-' }}, {{ $item->peralatan->gedung ?? '-' }})</span>
                                x{{ $item->jumlah }}
                            </div>
                        @endforeach
                    </td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->user->nama_user ?? '-' }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->tanggal_pinjam->format('d/m/Y') }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100"><span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded-full text-[10px] font-semibold">{{ $p->badge['label'] }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-gray-500">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        lpBuatDanUnduhPdf(@json($pdfHeaders), @json($pdfRows), @json($namaFile), {{ $pdfStatusIndex }});
    </script>
@endsection
