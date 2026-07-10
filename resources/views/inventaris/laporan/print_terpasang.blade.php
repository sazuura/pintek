@extends('layouts.print')

@section('content')
    <table class="w-full border-collapse text-[11px]">
        <thead>
            <tr>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold w-[30px]">#</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Nama Alat</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Gedung</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Lokasi Detail</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Tanggal Pasang</th>
                <th class="bg-primary text-white text-center py-2 px-2.5 font-semibold">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($terpasang as $index => $a)
                <tr class="even:bg-gray-50 align-top">
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $index + 1 }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $a->nama_alat }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $a->gedung }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $a->lokasi_detail ?? '-' }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center">{{ $a->tanggal_pasang->format('d/m/Y') }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100 text-center"><span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded-full text-[10px] font-semibold">{{ $a->kondisiLabel }}</span></td>
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
