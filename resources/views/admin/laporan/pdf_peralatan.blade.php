<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Peralatan — Diskominfotik</title>
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

        .terpasang {
            background: #e6f9f0;
            color: #1abc9c;
        }

        .belum {
            background: #fff4e5;
            color: #f39c12;
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
        <h2>LAPORAN PERALATAN DIGUNAKAN</h2>
        <p>Diskominfotik Kabupaten Bandung Barat</p>
        <p>Dicetak: {{ now()->translatedFormat('l, d F Y H:i') }} WIB</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Peralatan</th>
                <th>Kode Barang</th>
                <th>Gedung</th>
                <th>Kegiatan</th>
                <th>Tanggal</th>
                <th>Jml</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jadwalPeralatan as $i => $jp)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $jp->peralatan->nama_peralatan }}</td>
                    <td>{{ $jp->peralatan->kode_barang ?? '-' }}</td>
                    <td>{{ $jp->peralatan->gedung }}</td>
                    <td>{{ $jp->penjadwalan->judul_kegiatan }}</td>
                    <td>{{ $jp->penjadwalan->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $jp->jumlah }}</td>
                    <td>
                        <span class="badge {{ $jp->sudahDipasang() ? 'terpasang' : 'belum' }}">
                            {{ $jp->sudahDipasang() ? 'Terpasang' : 'Belum' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:15px;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Sistem Penjadwalan Zoom — Diskominfotik</div>
</body>

</html>