<?php
namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Styling bersama buat semua Export laporan (header biru bold, border tipis,
 * baris selang-seling, kolom Status diwarnai sesuai isinya, header dibekukan +
 * auto-filter) - dipakai lewat WithEvents supaya tidak perlu diulang di tiap
 * class Export.
 */
trait StyledExport
{
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $headerRange = "A1:{$highestColumn}1";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066FF']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);

                $dataRange = "A1:{$highestColumn}{$highestRow}";
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7F9FC']],
                        ]);
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($headerRange);

                $this->warnaiKolomStatus($sheet, $highestRow, $highestColumn);
            },
        ];
    }

    private function warnaiKolomStatus($sheet, int $highestRow, string $highestColumn): void
    {
        $headerCells = $sheet->rangeToArray("A1:{$highestColumn}1")[0];
        $statusColIndex = array_search('Status', $headerCells);
        if ($statusColIndex === false) {
            return;
        }
        $statusCol = Coordinate::stringFromColumnIndex($statusColIndex + 1);

        $peta = [
            'Aktif'          => ['bg' => 'E8F4FD', 'font' => '1D6FB8'],
            'Disetujui'      => ['bg' => 'E6F9F0', 'font' => '16825A'],
            'Selesai'        => ['bg' => 'E6F9F0', 'font' => '16825A'],
            'Menunggu'       => ['bg' => 'FFF4E5', 'font' => 'B9770E'],
            'Ditolak'        => ['bg' => 'FDECEA', 'font' => 'C0392B'],
            'Dibatalkan'     => ['bg' => 'FDECEA', 'font' => 'C0392B'],
            'Dikembalikan'   => ['bg' => 'E8F4FD', 'font' => '1D6FB8'],
            'Tersedia'       => ['bg' => 'E6F9F0', 'font' => '16825A'],
            'Hampir Habis'   => ['bg' => 'FFF4E5', 'font' => 'B9770E'],
            'Tidak Tersedia' => ['bg' => 'FDECEA', 'font' => 'C0392B'],
            'Baik'           => ['bg' => 'E6F9F0', 'font' => '16825A'],
            'Rusak'          => ['bg' => 'FDECEA', 'font' => 'C0392B'],
        ];

        for ($row = 2; $row <= $highestRow; $row++) {
            $nilai = $sheet->getCell("{$statusCol}{$row}")->getValue();
            if (!isset($peta[$nilai])) {
                continue;
            }
            $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $peta[$nilai]['font']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $peta[$nilai]['bg']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }
}
