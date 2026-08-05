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
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center"><x-badge :variant="$p->statusBadgeClass">{{ $p->statusLabel }}</x-badge></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-gray-500">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        lpBuatDanUnduhPdf(@json($pdfHeaders), @json($pdfRows), @json($namaFile), {{ $pdfStatusIndex }});
    </script>
@endsection
