@extends('layouts.print')

@section('content')
    <table class="w-full border-collapse text-[11px]">
        <thead>
            <tr>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">#</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Operator</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Judul Rapat</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Tanggal</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Platform</th>
                <th class="bg-primary text-white text-left py-2 px-2.5 font-semibold">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jadwal as $index => $j)
                @php
                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                    $dibatalkan = $j->isDibatalkan();
                    $label      = $dibatalkan ? 'Dibatalkan' : ($sudahLewat ? 'Selesai' : 'Aktif');
                    $badgeColor = $dibatalkan ? 'bg-red-100 text-red-700' : ($sudahLewat ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700');
                @endphp
                <tr class="even:bg-gray-50">
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $index + 1 }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $j->judul_kegiatan }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ $j->tanggal->translatedFormat('D, d/m/Y') }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100">{{ str_contains($j->platform, 'Online') ? 'Online' : 'Offline' }}</td>
                    <td class="py-1.5 px-2.5 border-b border-gray-100"><span class="{{ $badgeColor }} py-0.5 px-2 rounded-full text-[10px] font-semibold">{{ $label }}</span></td>
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
