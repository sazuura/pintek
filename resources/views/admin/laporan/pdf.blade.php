<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Presensi - Diskominfotik</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #3C91E6;
        }

        .header h2 {
            font-size: 16px;
            color: #3C91E6;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #3C91E6;
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
        }

        td {
            padding: 7px 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }

        tr:nth-child(even) td {
            background: #f9f9f9;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }

        .hadir {
            background: #e6f9f0;
            color: #1abc9c;
        }

        .izin_disetujui {
            background: #e8f4fd;
            color: #3498db;
        }

        .sakit_disetujui {
            background: #f3e8ff;
            color: #8b5cf6;
        }

        .alpha {
            background: #fdecea;
            color: #e74c3c;
        }

        .pending {
            background: #fff4e5;
            color: #f39c12;
        }

        .ditolak {
            background: #f1f1f1;
            color: #555;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #aaa;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>LAPORAN JADWAL & OPERATOR</h2>
        <p>Diskominfotik Kabupaten Bandung Barat</p>
        <p>Dicetak: {{ now()->translatedFormat('l, d F Y H:i') }} WIB</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Operator</th>
                <th>Judul Rapat</th>
                <th>Tanggal</th>
                <th>Platform</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jadwal as $index => $j)
                @php
                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                    $status     = $j->isDibatalkan() ? 'ditolak' : ($sudahLewat ? 'hadir' : 'pending');
                    $label      = $j->isDibatalkan() ? 'Dibatalkan' : ($sudahLewat ? 'Selesai' : 'Aktif');
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</td>
                    <td>{{ $j->judul_kegiatan }}</td>
                    <td>{{ $j->tanggal->translatedFormat('D, d/m/Y') }}</td>
                    <td>{{ str_contains($j->platform, 'Online') ? 'Online' : 'Offline' }}</td>
                    <td><span class="badge {{ $status }}">{{ $label }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:15px;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Sistem Penjadwalan Zoom - Diskominfotik</div>
</body>

</html>