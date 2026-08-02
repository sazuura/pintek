<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call(RoleAksesSeeder::class);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('peralatan')->truncate();
        DB::table('penjadwalan')->truncate();
        DB::table('peminjaman')->truncate();
        DB::table('peminjaman_item')->truncate();
        DB::table('jadwal_operator')->truncate();
        DB::table('jadwal_peralatan')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $today = Carbon::today();

        $pilihBerbobot = function (array $bobot) {
            $total = array_sum($bobot);
            $acak  = random_int(1, $total);
            $kumulatif = 0;
            foreach ($bobot as $opsi => $nilai) {
                $kumulatif += $nilai;
                if ($acak <= $kumulatif) {
                    return $opsi;
                }
            }
            return array_key_first($bobot);
        };

        $users = [
            [
                'id_user'       => 'USR-10001',
                'nama_user'     => 'Admin Sazuura',
                'jenis_kelamin' => 'L',
                'alamat'        => 'Jl. Raya Ngamprah No. 1 (Kompleks Perkantoran Pemda KBB), Ngamprah, Kabupaten Bandung Barat',
                'nohp'          => '081222330001',
                'email'         => 'admin@diskominfotik.go.id',
                'password'      => Hash::make('password'),
                'role'          => 'admin',
                'status'        => 'active',
            ],
            [
                'id_user'       => 'USR-10002',
                'nama_user'     => 'Fadhil (Staf Inventaris Utama)',
                'jenis_kelamin' => 'L',
                'alamat'        => 'Jl. Raya Ngamprah No. 2, Ngamprah, Kabupaten Bandung Barat',
                'nohp'          => '085722330002',
                'email'         => 'inventaris@diskominfotik.go.id',
                'password'      => Hash::make('password'),
                'role'          => 'inventaris',
                'status'        => 'active',
            ],
        ];

        $operatorTemplate = [
            ['nama' => 'Rian Setiawan',    'jk' => 'L', 'alamat' => 'Jl. Raya Padalarang No. 45, Padalarang, Kabupaten Bandung Barat',     'hp' => '081322330011'],
            ['nama' => 'Dewi Anggraeni',   'jk' => 'P', 'alamat' => 'Jl. Kolonel Masturi No. 12, Lembang, Kabupaten Bandung Barat',         'hp' => '081322330012'],
            ['nama' => 'Muhammad Fajar',   'jk' => 'L', 'alamat' => 'Jl. Raya Cililin No. 8, Cililin, Kabupaten Bandung Barat',             'hp' => '081322330013'],
            ['nama' => 'Siti Nurhaliza',   'jk' => 'P', 'alamat' => 'Jl. Terusan Batujajar No. 20, Batujajar, Kabupaten Bandung Barat',      'hp' => '081322330014'],
            ['nama' => 'Agus Permana',     'jk' => 'L', 'alamat' => 'Jl. Raya Cikalongwetan No. 33, Cikalongwetan, Kabupaten Bandung Barat', 'hp' => '081322330015'],
        ];
        foreach ($operatorTemplate as $i => $op) {
            $users[] = [
                'id_user'       => 'USR-1010' . $i,
                'nama_user'     => $op['nama'] . ' (Operator)',
                'jenis_kelamin' => $op['jk'],
                'alamat'        => $op['alamat'],
                'nohp'          => $op['hp'],
                'email'         => 'operator' . ($i + 1) . '@diskominfotik.go.id',
                'password'      => Hash::make('password'),
                'role'          => 'operator',

                'status'        => $op['nama'] === 'Agus Permana' ? 'inactive' : 'active',
            ];
        }

        DB::table('users')->insert($users);

        $operatorIds = DB::table('users')->where('role', 'operator')->pluck('id_user')->toArray();

        $gedungList = ['Gedung A (Kominfo)', 'Gedung B (Persandian)', 'Gedung C (TIK)'];
        $lokasiList = ['Ruang Rapat Utama', 'Aula Lantai 2', 'Gudang Logistik', 'Ruang Server'];

        $barangTemplate = [
            ['nama' => 'Laptop ASUS ExpertBook',          'kode' => 'LPT', 'foto' => 'peralatan/peralatan-lpt.jpg', 'stok' => 6,  'rusak' => 1],
            ['nama' => 'Proyektor Epson EB-X400',         'kode' => 'PRJ', 'foto' => 'peralatan/peralatan-prj.jpg', 'stok' => 4,  'rusak' => 0],
            ['nama' => 'Printer HP Laserjet Pro',         'kode' => 'PRN', 'foto' => 'peralatan/peralatan-prn.jpg', 'stok' => 3,  'rusak' => 1],
            ['nama' => 'Pointer Logitech Spotlight',      'kode' => 'PTR', 'foto' => 'peralatan/peralatan-ptr.jpg', 'stok' => 8,  'rusak' => 0],
            ['nama' => 'Sound System Portable Speaker',   'kode' => 'SND', 'foto' => 'peralatan/peralatan-snd.jpg', 'stok' => 5,  'rusak' => 1],
            ['nama' => 'Wireless Microphone Shure',       'kode' => 'MIC', 'foto' => 'peralatan/peralatan-mic.jpg', 'stok' => 10, 'rusak' => 1],
            ['nama' => 'Kabel HDMI 15 Meter',              'kode' => 'CBL', 'foto' => 'peralatan/peralatan-cbl.jpg', 'stok' => 15, 'rusak' => 2],
            ['nama' => 'Televisi LED Polytron 43 Inch',   'kode' => 'TVL', 'foto' => 'peralatan/peralatan-tvl.jpg', 'stok' => 3,  'rusak' => 0],
            ['nama' => 'Router Cisco Wi-Fi Pod',          'kode' => 'RTR', 'foto' => 'peralatan/peralatan-rtr.jpg', 'stok' => 6,  'rusak' => 0],
            ['nama' => 'Kamera DSLR Canon EOS',           'kode' => 'CAM', 'foto' => 'peralatan/peralatan-cam.jpg', 'stok' => 4,  'rusak' => 1],
            ['nama' => 'Tripod Takara Profesional',       'kode' => 'TPD', 'foto' => 'peralatan/peralatan-tpd.jpg', 'stok' => 7,  'rusak' => 0],
            ['nama' => 'Webcam Logitech Brio 4K',          'kode' => 'WBC', 'foto' => 'peralatan/peralatan-wbc.jpg', 'stok' => 9,  'rusak' => 1],
            ['nama' => 'Converter Type-C to HDMI',         'kode' => 'CNV', 'foto' => 'peralatan/peralatan-cnv.jpg', 'stok' => 12, 'rusak' => 2],
            ['nama' => 'Gimbal Stabilizer DJI Ronin',      'kode' => 'GMB', 'foto' => 'peralatan/peralatan-gmb.jpg', 'stok' => 3,  'rusak' => 0],
            ['nama' => 'UPS APC 700VA',                    'kode' => 'UPS', 'foto' => 'peralatan/peralatan-ups.jpg', 'stok' => 5,  'rusak' => 1],
        ];

        $catatanPeralatan = [
            'Baterai cadangan disarankan dibawa untuk pemakaian di luar ruangan.',
            'Kabel power sedikit longgar, perlu pengecekan berkala.',
            'Unit hasil pengadaan tahun ini, kondisi masih sangat baik.',
            'Perlu dibersihkan setelah setiap pemakaian di luar ruangan.',
        ];

        $peralatanIds = [];
        $urutSeri     = 1;

        foreach ($gedungList as $gIndex => $gedung) {
            foreach ($barangTemplate as $barang) {
                $idAlat = 'A-' . $barang['kode'] . '-' . str_pad($urutSeri, 3, '0', STR_PAD_LEFT);
                $urutSeri++;

                $peralatanIds[] = $idAlat;

                $stok  = max(1, $barang['stok'] - $gIndex);
                $rusak = $gIndex === 1 ? $barang['rusak'] : max(0, $barang['rusak'] - 1);

                DB::table('peralatan')->insert([
                    'id_peralatan'   => $idAlat,
                    'kode_barang'    => 'INV-' . $barang['kode'] . '-' . str_pad($urutSeri, 4, '0', STR_PAD_LEFT),
                    'nama_peralatan' => $barang['nama'],
                    'gedung'         => $gedung,
                    'lokasi_detail'  => $lokasiList[$urutSeri % count($lokasiList)],
                    'stok'           => $stok,
                    'rusak'          => $rusak,
                    'keterangan'     => $rusak > 0 ? $catatanPeralatan[$urutSeri % count($catatanPeralatan)] : null,
                    'foto'           => $barang['foto'],
                ]);
            }
        }

        $judulKegiatan = [
            'Rapat Koordinasi Evaluasi SPBE Triwulan',
            'Bimbingan Teknis Pengelolaan Website Desa',
            'Sosialisasi Cyber Security Awareness',
            'Focus Group Discussion Pengembangan Smart City',
            'Pelatihan Jurnalistik dan Kehumasan Digital',
            'Rapat Integrasi Satu Data Kabupaten Bandung Barat',
            'Workshop Pengembangan Aplikasi Layanan Internal',
            'Audiensi Implementasi E-Office dengan OPD',
            'Rapat Koordinasi Mingguan Diskominfotik',
            'Sosialisasi Penerapan Tanda Tangan Elektronik',
            'Rapat Persiapan Musrenbang Bidang TIK',
            'Bimtek Pengelolaan Media Sosial Pemerintah Daerah',
            'Rapat Evaluasi Jaringan Internet OPD',
            'Koordinasi Pengembangan Aplikasi SIMPEG',
            'Rapat Pembahasan Anggaran Belanja TIK',
            'Sosialisasi Perlindungan Data Pribadi',
            'Rapat Koordinasi Command Center KBB',
            'Pelatihan Pengelolaan Konten Digital Humas',
            'Rapat Persiapan Rapat Koordinasi Diskominfotik Provinsi',
            'Audiensi Pengembangan Aplikasi dengan Dinas Terkait',
        ];
        $catatanJadwal = [
            'Membahas capaian dan kendala implementasi di lingkungan Pemkab Bandung Barat.',
            'Koordinasi lanjutan terkait progres pekerjaan bulan berjalan.',
            'Peserta diharapkan hadir 15 menit sebelum acara dimulai.',
            'Dokumentasi dan notulen akan dibagikan setelah kegiatan selesai.',
            null,
            null,
        ];
        $alasanBatalJadwal = ['Kuorum tidak terpenuhi', 'Jadwal bentrok dengan pimpinan', 'Teknis jaringan bermasalah'];
        $lokasiFisikList = [
            'Ruang Rapat Utama, Kantor Diskominfotik KBB, Ngamprah',
            'Aula Lantai 2, Kantor Diskominfotik KBB, Ngamprah',
            'Ruang Rapat Bupati, Kompleks Perkantoran Pemda KBB, Ngamprah',
        ];

        $awalRentang  = $today->copy()->subMonths(5)->startOfWeek(Carbon::MONDAY);
        $akhirRentang = $today->copy()->addMonths(2)->endOfWeek(Carbon::SUNDAY);

        $jadwalPerOperator = [];
        $judulJadwalById   = [];
        $urutJadwal        = 1;

        $cursorMinggu = $awalRentang->copy();
        while ($cursorMinggu->lte($akhirRentang)) {
            $hariKerja = [0, 1, 2, 3, 4];
            shuffle($hariKerja);
            $jumlahRapatMinggu = random_int(2, 3);
            $hariTerpilih = array_slice($hariKerja, 0, $jumlahRapatMinggu);

            foreach ($hariTerpilih as $offsetHari) {
                $tanggal = $cursorMinggu->copy()->addDays($offsetHari);

                $idJadwal = 'JDW-' . str_pad($urutJadwal, 5, '0', STR_PAD_LEFT);
                $urutJadwal++;

                $bisaDibatalkan = $tanggal->lt($today) && random_int(1, 100) <= 15;
                $status         = $bisaDibatalkan ? 'dibatalkan' : 'selesai';
                $alasanBatal    = $bisaDibatalkan ? $alasanBatalJadwal[array_rand($alasanBatalJadwal)] : null;

                $jamMulai   = [ '09:00:00', '10:00:00', '13:30:00' ][array_rand([0, 1, 2])];
                $jamSelesai = date('H:i:s', strtotime($jamMulai) + (3600 * random_int(1, 3)));
                $judul      = $judulKegiatan[array_rand($judulKegiatan)];

                if ($tanggal->gte($today->copy()->subDays(14)) && !$bisaDibatalkan) {
                    $createdAt = now()->subDays(random_int(0, 13))->subMinutes(random_int(0, 500));
                } else {
                    $createdAt = $tanggal->copy()->subDays(random_int(3, 6));
                }

                $platform = $pilihBerbobot(['Offline' => 55, 'Online (Zoom)' => 30, 'Hybrid' => 15]);

                $dibatalkanAt = $bisaDibatalkan
                    ? $tanggal->copy()->subDays(random_int(1, 2))->addHours(random_int(8, 16))
                    : null;

                DB::table('penjadwalan')->insert([
                    'id_penjadwalan' => $idJadwal,
                    'judul_kegiatan' => $judul,
                    'tanggal'        => $tanggal->format('Y-m-d'),
                    'waktu_mulai'    => $jamMulai,
                    'waktu_selesai'  => $jamSelesai,
                    'platform'       => $platform,
                    'keterangan'     => $catatanJadwal[array_rand($catatanJadwal)],
                    'lokasi_fisik'   => $platform === 'Hybrid' ? $lokasiFisikList[array_rand($lokasiFisikList)] : null,
                    'status'         => $status,
                    'alasan_batal'   => $alasanBatal,
                    'dibatalkan_at'  => $dibatalkanAt,
                    'created_at'     => $createdAt,
                    'updated_at'     => $dibatalkanAt ?? $createdAt,
                ]);

                $judulJadwalById[$idJadwal] = $judul;

                $operatorTerpilih = (array) array_rand(array_flip($operatorIds), min(random_int(1, 2), count($operatorIds)));
                foreach ($operatorTerpilih as $idOperator) {
                    DB::table('jadwal_operator')->insert([
                        'id_penjadwalan' => $idJadwal,
                        'id_user'        => $idOperator,
                    ]);
                    $jadwalPerOperator[$idOperator][] = $idJadwal;
                }

                if ($status !== 'dibatalkan' && random_int(1, 100) <= 70) {
                    $jumlahAlat  = random_int(1, 3);
                    $alatDipilih = (array) array_rand(array_flip($peralatanIds), $jumlahAlat);
                    foreach ($alatDipilih as $idAlat) {
                        DB::table('jadwal_peralatan')->insert([
                            'id_penjadwalan' => $idJadwal,
                            'id_peralatan'   => $idAlat,
                            'jumlah'         => random_int(1, 2),
                            'created_at'     => $createdAt,
                            'updated_at'     => $createdAt,
                        ]);
                    }
                }
            }

            $cursorMinggu->addWeek();
        }

        $keperluanMandiri = [
            'Dokumentasi kegiatan lapangan Diskominfotik',
            'Backup dan pemulihan sistem jaringan Puskesmas',
            'Operasional monitoring Command Center KBB',
            'Liputan kegiatan Bupati Bandung Barat',
            'Studi banding pengelolaan TIK ke daerah lain',
            'Pemeliharaan jaringan internet kantor kecamatan',
        ];
        $catatanKembaliBaik = [
            'Kondisi barang lengkap dan baik saat dikembalikan.',
            'Semua unit dikembalikan dalam kondisi baik dan berfungsi normal.',
            'Barang dikembalikan lengkap, kabel sedikit kusut namun masih berfungsi.',
        ];
        $alasanTolakBatal = [
            'Stok peralatan sedang tidak tersedia pada tanggal tersebut.',
            'Kegiatan terkait dibatalkan oleh penyelenggara.',
            'Jadwal peminjaman bentrok dengan kegiatan lain.',
        ];

        $totalPeminjaman = 260;
        for ($i = 1; $i <= $totalPeminjaman; $i++) {
            $idPeminjaman = 'PMJ-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $idPeminjam   = $operatorIds[array_rand($operatorIds)];

            $offsetHari = random_int(
                -1 * abs($today->diffInDays($today->copy()->subMonths(5))),
                abs($today->diffInDays($today->copy()->addMonths(2)))
            );
            $tglPinjam = $today->copy()->addDays($offsetHari);

            if ($tglPinjam->gt($today)) {
                $status = $pilihBerbobot(['diajukan' => 40, 'disetujui' => 50, 'ditolak' => 10]);
            } elseif ($tglPinjam->gte($today->copy()->subDays(5))) {
                $status = $pilihBerbobot(['diajukan' => 15, 'disetujui' => 35, 'dikembalikan' => 35, 'ditolak' => 15]);
            } else {
                $status = $pilihBerbobot(['dikembalikan' => 75, 'ditolak' => 10, 'dibatalkan' => 10, 'disetujui' => 5]);
            }

            $tglKembaliRcn = $tglPinjam->copy()->addDays(random_int(1, 5));

            $tglKembaliAkt = $status === 'dikembalikan' ? $tglKembaliRcn->copy()->min($today) : null;
            $alasanBatal   = in_array($status, ['ditolak', 'dibatalkan'], true) ? $alasanTolakBatal[array_rand($alasanTolakBatal)] : null;
            $catatanInv    = $status === 'dikembalikan' ? $catatanKembaliBaik[array_rand($catatanKembaliBaik)] : null;

            $idPenjadwalanTerkait = null;
            $keperluan            = $keperluanMandiri[array_rand($keperluanMandiri)];
            if (!empty($jadwalPerOperator[$idPeminjam]) && random_int(1, 100) <= 30) {
                $idPenjadwalanTerkait = $jadwalPerOperator[$idPeminjam][array_rand($jadwalPerOperator[$idPeminjam])];
                $keperluan = 'Kebutuhan peralatan untuk ' . $judulJadwalById[$idPenjadwalanTerkait];
            }

            if ($tglPinjam->gt($today)) {
                $createdAt = now()->subDays(random_int(0, 13))->subMinutes(random_int(0, 500));
            } else {
                $createdAt = $tglPinjam->copy()->subDays(random_int(1, 3))->addHours(random_int(8, 16));
            }

            $updatedAt = match (true) {
                $status === 'diajukan'     => $createdAt,
                $status === 'dikembalikan' => $tglKembaliAkt->copy()->addHours(random_int(8, 17)),
                default                    => $createdAt->copy()->addDays(random_int(0, 2))->addHours(random_int(1, 8))->min(now()),
            };

            DB::table('peminjaman')->insert([
                'id_peminjaman'           => $idPeminjaman,
                'id_user'                 => $idPeminjam,
                'id_penjadwalan'          => $idPenjadwalanTerkait,
                'tanggal_pinjam'          => $tglPinjam->format('Y-m-d'),
                'tanggal_kembali_rencana' => $tglKembaliRcn->format('Y-m-d'),
                'tanggal_kembali_aktual'  => $tglKembaliAkt?->format('Y-m-d'),
                'keperluan'               => $keperluan,
                'status'                  => $status,
                'catatan_inventaris'      => $catatanInv,
                'alasan_batal'            => $alasanBatal,

                'dibatalkan_at'           => $status === 'dibatalkan' ? $updatedAt : null,
                'created_at'              => $createdAt,
                'updated_at'              => $updatedAt,
            ]);

            $statusItem = match ($status) {
                'disetujui', 'dikembalikan' => 'disetujui',
                'ditolak'                   => 'ditolak',
                default                     => 'diajukan',
            };

            $itemPinjam = (array) array_rand(array_flip($peralatanIds), random_int(1, 3));
            foreach ($itemPinjam as $alatId) {
                DB::table('peminjaman_item')->insert([
                    'id_peminjaman' => $idPeminjaman,
                    'id_peralatan'  => $alatId,
                    'jumlah'        => random_int(1, 2),
                    'status'        => $statusItem,
                ]);
            }
        }
    }
}
