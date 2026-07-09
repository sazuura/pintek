@extends('layouts.print')

@section('content')
    <table class="w-full border-collapse text-[11px]">
        <thead>
            <tr>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold w-[30px]">#</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Nama Alat</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Kode Barang</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Gedung</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Stok Total</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Rusak</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Tersedia</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stok as $index => $p)
                <tr class="even:bg-gray-50 align-top">
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $index + 1 }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->nama_peralatan }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->kode_barang ?? '-' }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $p->gedung }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center">{{ $p->stok }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center">{{ $p->rusak ?? 0 }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center">{{ $p->stok_tersedia }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center"><span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded-full text-[10px] font-semibold">{{ $p->statusLabel }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-gray-500">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        lpBuatDanUnduhPdf(@json($pdfHeaders), @json($pdfRows), @json($namaFile), {{ $pdfStatusIndex }});
    </script>
@endsection
