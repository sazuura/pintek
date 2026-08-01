<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $namaFile }}</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <style>
        @media print {
            @page { margin: 14mm; }
        }
    </style>
    <script>
        // Warna status disamakan dengan badge di tampilan live & Excel - dipetakan
        // sekali di sini supaya dipakai bareng oleh laporan jadwal & peralatan.
        // Didefinisikan di head (bukan di akhir body) supaya sudah tersedia
        // sebelum dipanggil dari script di konten anak (child view).
        var LP_STATUS_COLORS = {
            'Aktif':          { bg: [232, 244, 253], text: [29, 111, 184] },
            'Disetujui':      { bg: [230, 249, 240], text: [22, 130, 90] },
            'Selesai':        { bg: [230, 249, 240], text: [22, 130, 90] },
            'Menunggu':       { bg: [255, 244, 229], text: [185, 119, 14] },
            'Ditolak':        { bg: [253, 236, 234], text: [192, 57, 43] },
            'Dibatalkan':     { bg: [253, 236, 234], text: [192, 57, 43] },
            'Dikembalikan':   { bg: [232, 244, 253], text: [29, 111, 184] },
            'Tersedia':       { bg: [230, 249, 240], text: [22, 130, 90] },
            'Hampir Habis':   { bg: [255, 244, 229], text: [185, 119, 14] },
            'Tidak Tersedia': { bg: [253, 236, 234], text: [192, 57, 43] },
            'Baik':           { bg: [230, 249, 240], text: [22, 130, 90] },
            'Rusak':          { bg: [253, 236, 234], text: [192, 57, 43] },
        };

        /**
         * Bikin PDF asli (teks vektor, bisa di-select/copy/search) dari data
         * headers+rows pakai jsPDF + AutoTable, lalu langsung diunduh browser -
         * tanpa dialog print, karena ini file yang benar-benar di-generate di JS
         * (bukan print-to-PDF bawaan browser yang wajib lewat dialog).
         *
         * @param {string[]} headers
         * @param {Array<Array<string>>} rows
         * @param {string} namaFile  tanpa ekstensi
         * @param {number} statusColIndex  index kolom Status (0-based) buat pewarnaan, -1 kalau tidak ada
         */
        function lpBuatDanUnduhPdf(headers, rows, namaFile, statusColIndex) {
            try {
                var jsPDF = window.jspdf.jsPDF;
                var doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

                doc.setFontSize(13);
                doc.setTextColor(0, 102, 255);
                doc.text(@json($judul), 14, 12);
                doc.setFontSize(9);
                doc.setTextColor(120, 120, 120);
                doc.text('Diskominfotik Kabupaten Bandung Barat', 14, 17);
                doc.text('Dicetak: {{ now()->translatedFormat("l, d F Y H:i") }} WIB', 14, 21);

                doc.autoTable({
                    head: [headers],
                    body: rows,
                    startY: 26,
                    styles: { fontSize: 8, cellPadding: 2, valign: 'middle' },
                    headStyles: { fillColor: [0, 102, 255], textColor: [255, 255, 255], fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [247, 249, 252] },
                    didParseCell: function (data) {
                        if (statusColIndex >= 0 && data.section === 'body' && data.column.index === statusColIndex) {
                            var warna = LP_STATUS_COLORS[data.cell.raw];
                            if (warna) {
                                data.cell.styles.fillColor = warna.bg;
                                data.cell.styles.textColor = warna.text;
                                data.cell.styles.fontStyle = 'bold';
                            }
                        }
                    },
                });

                doc.save(namaFile + '.pdf');

                var status = document.getElementById('lp-status');
                status.className = 'print:hidden mb-4 py-2.5 px-3.5 rounded-lg bg-success text-success-text text-[13px] font-medium flex items-center gap-2';
                status.innerHTML = '<i class="bx bx-check-circle"></i> PDF berhasil diunduh otomatis. Tab ini bisa ditutup, atau pakai tombol di kanan bawah untuk print/simpan ulang.';
            } catch (e) {
                console.error('Gagal membuat PDF otomatis:', e);
                var status = document.getElementById('lp-status');
                status.className = 'print:hidden mb-4 py-2.5 px-3.5 rounded-lg bg-danger text-danger-text text-[13px] font-medium flex items-center gap-2';
                status.innerHTML = '<i class="bx bx-error-circle"></i> Gagal mengunduh otomatis. Pakai tombol Print di kanan bawah untuk simpan manual.';
            }
        }
    </script>
</head>

<body class="bg-white font-sans text-[#222] p-6 print:p-0">
    <div class="max-w-[1000px] mx-auto">
        <div id="lp-status" class="print:hidden mb-4 py-2.5 px-3.5 rounded-lg bg-primary-50 text-primary text-[13px] font-medium flex items-center gap-2">
            <i class="bx bx-loader-alt bx-spin"></i> Menyiapkan PDF untuk diunduh otomatis...
        </div>

        <div class="text-center mb-6 pb-3 border-b-2 border-primary">
            <h2 class="text-lg font-bold text-primary m-0">{{ $judul }}</h2>
            <p class="text-xs text-gray-500 m-0">Diskominfotik Kabupaten Bandung Barat</p>
            <p class="text-xs text-gray-500 m-0">Dicetak: {{ now()->translatedFormat('l, d F Y H:i') }} WIB</p>
        </div>

        @yield('content')
    </div>

    <button type="button" onclick="window.print()"
        class="print:hidden fixed bottom-5 right-5 h-11 px-5 rounded-full bg-primary text-white border-0 shadow-lg cursor-pointer font-semibold text-sm flex items-center gap-2">
        <i class="bx bx-printer"></i> Print / Simpan Ulang
    </button>
</body>

</html>
