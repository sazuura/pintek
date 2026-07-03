<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Peralatan - Diskominfotik</title>
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

        .badge-warning { background: #fff4e5; color: #f39c12; }
        .badge-active  { background: #e6f9f0; color: #1abc9c; }
        .badge-danger  { background: #fdecea; color: #e74c3c; }
        .badge-info    { background: #e8f4fd; color: #3498db; }

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
        <h2>LAPORAN PERALATAN DIGUNAKAN</h2>
        <p>Diskominfotik Kabupaten Bandung Barat</p>
        <p>Dicetak: {{ now()->translatedFormat('l, d F Y H:i') }} WIB</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Peralatan</th>
                <th>Nomor Seri</th>
                <th>Gedung</th>
                <th>Peminjam</th>
                <th>Tanggal Pinjam</th>
                <th>Jml</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($peralatan as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->peralatan->nama_peralatan }}</td>
                    <td>{{ $item->peralatan->kode_barang ?? '-' }}</td>
                    <td>{{ $item->peralatan->gedung }}</td>
                    <td>{{ $item->peminjaman->user->nama_user ?? '-' }}</td>
                    <td>{{ $item->peminjaman->tanggal_pinjam->format('d/m/Y') }}</td>
                    <td>{{ $item->jumlah }}</td>
                    <td><span class="badge {{ $item->peminjaman->badge['class'] }}">{{ $item->peminjaman->badge['label'] }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:15px;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Sistem Penjadwalan Zoom - Diskominfotik</div>
</body>

</html>
