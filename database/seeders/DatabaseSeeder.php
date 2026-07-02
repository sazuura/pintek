<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); 
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('peralatan')->truncate();
        DB::table('penjadwalan')->truncate();
        DB::table('absensi')->truncate();
        DB::table('dokumentasi')->truncate();
        DB::table('peminjaman')->truncate();
        DB::table('peminjaman_item')->truncate();
        DB::table('jadwal_peralatan')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. GENERATE USERS (7 Orang)
        $gedungList = ['Gedung A (Kominfo)', 'Gedung B (Persandian)', 'Gedung C (TIK)'];
        $users = [];

        // 1 Admin
        $users[] = [
            'id_user'   => 'USR-' . $faker->unique()->numerify('#####'),
            'nama_user' => 'Admin Sazuura',
            'nohp'      => '0812' . $faker->numerify('########'),
            'email'     => 'admin@diskominfotik.go.id',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
            'status'    => 'active',
            'gedung'    => null,
        ];

        // 1 Inventaris Utama (Sekarang menghandle semua gedung, kolom gedung di-set null/global)
        $users[] = [
            'id_user'   => 'USR-' . $faker->unique()->numerify('#####'),
            'nama_user' => 'Fadhil (Staf Inventaris Utama)',
            'nohp'      => '0857' . $faker->numerify('########'),
            'email'     => 'inventaris@diskominfotik.go.id',
            'password'  => Hash::make('password'),
            'role'      => 'inventaris',
            'status'    => 'active',
            'gedung'    => null, 
        ];

        // 5 Operator
        for ($i = 1; $i <= 5; $i++) {
            $users[] = [
                'id_user'   => 'USR-' . $faker->unique()->numerify('#####'),
                'nama_user' => $faker->name . ' (Operator)',
                'nohp'      => '0813' . $faker->numerify('########'),
                'email'     => 'operator' . $i . '@diskominfotik.go.id',
                'password'  => Hash::make('password'),
                'role'      => 'operator',
                'status'    => $faker->randomElement(['active', 'active', 'active', 'inactive']),
                'gedung'    => null,
            ];
        }

        DB::table('users')->insert($users);

        // Ambil list ID user berdasarkan role untuk relasi ke tabel lain
        $allUserIds     = DB::table('users')->pluck('id_user')->toArray();
        $operatorIds    = DB::table('users')->where('role', 'operator')->pluck('id_user')->toArray();
        $peminjamIds    = DB::table('users')->whereIn('role', ['operator', 'admin'])->pluck('id_user')->toArray();

        // 2. GENERATE PERALATAN (3 Gedung x 15 Barang = 45 Barang Kantor Riil)
        $barangTemplate = [
            ['nama' => 'Laptop ASUS ExpertBook', 'kode' => 'LPT'],
            ['nama' => 'Proyektor Epson EB-X400', 'kode' => 'PRJ'],
            ['nama' => 'Printer HP Laserjet Pro', 'kode' => 'PRN'],
            ['nama' => 'Pointer Logitech Spotlight', 'kode' => 'PTR'],
            ['nama' => 'Sound System Portable Speaker', 'kode' => 'SND'],
            ['nama' => 'Wireless Microphone Shure', 'kode' => 'MIC'],
            ['nama' => 'Kabel HDMI 15 Meter', 'kode' => 'CBL'],
            ['nama' => 'Televisi LED Polytron 43 Inch', 'kode' => 'TVL'],
            ['nama' => 'Router Cisco Wi-Fi Pod', 'kode' => 'RTR'],
            ['nama' => 'Kamera DSLR Canon EOS', 'kode' => 'CAM'],
            ['nama' => 'Tripod Takara Profesional', 'kode' => 'TPD'],
            ['nama' => 'Webcam Logitech Brio 4K', 'kode' => 'WBC'],
            ['nama' => 'Converter Type-C to HDMI', 'kode' => 'CNV'],
            ['nama' => 'Gimbal Stabilizer DJI Ronin', 'kode' => 'GMB'],
            ['nama' => 'UPS APC 700VA', 'kode' => 'UPS']
        ];

        $peralatanIds = [];

        foreach ($gedungList as $gedung) {
            foreach ($barangTemplate as $barang) {
                $stok      = $faker->numberBetween(5, 20);
                $rusak     = $faker->optional(0.3, 0)->numberBetween(0, 2); 
                $perbaikan = $faker->optional(0.2, 0)->numberBetween(0, 1); 

                $idAlat = 'A-' . strtoupper($barang['kode']) . '-' . $faker->unique()->numerify('###');
                $peralatanIds[] = $idAlat;

                DB::table('peralatan')->insert([
                    'id_peralatan'   => $idAlat,
                    'kode_barang'    => 'INV-' . strtoupper($barang['kode']) . '-' . $faker->unique()->numerify('####'),
                    'nama_peralatan' => $barang['nama'],
                    'gedung'         => $gedung, // Barang tetap tersebar di berbagai gedung
                    'lokasi_detail'  => $faker->randomElement(['Ruang Rapat Utama', 'Aula Lantai 2', 'Gudang Logistik', 'Ruang Server']),
                    'stok'           => $stok,
                    'rusak'          => $rusak,
                    'perbaikan'      => $perbaikan,
                    'keterangan'     => $faker->optional(0.4)->sentence(),
                    'foto'           => 'peralatan/' . $faker->numberBetween(1, 10) . '.jpg',
                ]);
            }
        }

        // 3. GENERATE JADWAL & ABSENSI (150 Data)
        $judulKegiatan = [
            'Rapat Koordinasi Evaluasi SPBE', 'Bimtek Pengelolaan Website Desa',
            'Sosialisasi Cyber Security Awareness', 'Focus Group Discussion Smart City',
            'Pelatihan Jurnalistik & Kehumasan', 'Rapat Integrasi Satu Data KBB',
            'Workshop Pengembangan Aplikasi Internal', 'Audiensi Implementasi E-Office'
        ];

        for ($i = 1; $i <= 150; $i++) {
            $idJadwal = 'JDW-' . $faker->unique()->numerify('#####');
            $tanggal  = $faker->dateTimeBetween('-3 months', '+1 months')->format('Y-m-d');
            $status   = $faker->randomElement(['selesai', 'selesai', 'selesai', 'dibatalkan']);
            $alasanBatal = ($status === 'dibatalkan') ? $faker->randomElement(['Kuorum tidak terpenuhi', 'Jadwal bentrok dengan pimpinan', 'Teknis jaringan bermasalah']) : null;

            $jamMulai = $faker->randomElement(['09:00:00', '10:00:00', '13:30:00']);
            $jamSelesai = date('H:i:s', strtotime($jamMulai) + (3600 * $faker->numberBetween(1, 3)));

            DB::table('penjadwalan')->insert([
                'id_penjadwalan' => $idJadwal,
                'judul_kegiatan' => $faker->randomElement($judulKegiatan) . ' Angkatan ' . $faker->numberBetween(1, 5),
                'tanggal'        => $tanggal,
                'waktu_mulai'    => $jamMulai,
                'waktu_selesai'  => $jamSelesai,
                'platform'       => $faker->randomElement(['Offline', 'Zoom Cloud Meetings', 'Google Meet']),
                'keterangan'     => $faker->sentence(),
                'id_pemateri'    => $faker->randomElement($operatorIds),
                'status'         => $status,
                'alasan_batal'   => $alasanBatal,
            ]);

            $pesertaIds = $faker->randomElements($operatorIds, $faker->numberBetween(1, 3));
            foreach ($pesertaIds as $pesertaId) {
                $statusAbsen = $faker->randomElement(['hadir', 'hadir', 'hadir', 'izin', 'sakit', 'alpha']);
                
                $idAbsensi = DB::table('absensi')->insertGetId([
                    'id_penjadwalan' => $idJadwal,
                    'id_user'        => $pesertaId,
                    'tanggal'        => $tanggal,
                    'status'         => $statusAbsen,
                    'validated'      => $faker->boolean(80),
                ]);

                if (in_array($statusAbsen, ['hadir', 'izin', 'sakit'])) {
                    $totalDokumen = $faker->numberBetween(1, 2);
                    for ($d = 0; $d < $totalDokumen; $d++) {
                        DB::table('dokumentasi')->insert([
                            'id_absensi' => $idAbsensi,
                            'file_path'  => 'dokumentasi/sample-absen-' . $faker->numberBetween(1, 5) . '.jpg',
                            'keterangan' => 'Bukti ' . $statusAbsen,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            if ($faker->boolean(70) && $status !== 'dibatalkan') {
                $alatTerpakai = $faker->randomElements($peralatanIds, $faker->numberBetween(1, 3));
                foreach ($alatTerpakai as $alatId) {
                    DB::table('jadwal_peralatan')->insert([
                        'id_penjadwalan'    => $idJadwal,
                        'id_peralatan'       => $alatId,
                        'jumlah'            => $faker->numberBetween(1, 2),
                        'status_pemasangan' => $faker->randomElement(['sudah_dipasang', 'belum_dipasang']),
                    ]);
                }
            }
        }

        // 4. GENERATE PEMINJAMAN (100 Data)
        for ($i = 1; $i <= 100; $i++) {
            $statusPinjam  = $faker->randomElement(['diajukan', 'disetujui', 'ditolak', 'dikembalikan', 'dibatalkan']);
            $tglPinjam     = $faker->dateTimeBetween('-2 months', '+2 weeks');
            $tglKembaliRcn = clone $tglPinjam;
            $tglKembaliRcn->modify('+' . $faker->numberBetween(1, 5) . ' days');
            
            $tglKembaliAkt = ($statusPinjam === 'dikembalikan') ? clone $tglKembaliRcn : null;
            $alasanBatal   = ($statusPinjam === 'dibatalkan' || $statusPinjam === 'ditolak') ? $faker->sentence() : null;

            $idPeminjaman = DB::table('peminjaman')->insertGetId([
                'id_user'                 => $faker->randomElement($peminjamIds),
                'tanggal_pinjam'          => $tglPinjam->format('Y-m-d'),
                'tanggal_kembali_rencana' => $tglKembaliRcn->format('Y-m-d'),
                'tanggal_kembali_aktual'  => $tglKembaliAkt ? $tglKembaliAkt->format('Y-m-d') : null,
                'keperluan'               => $faker->randomElement(['Liputan Acara Bupati', 'Studi Banding Dinas', 'Backup Sistem Puskesmas', 'Operasional Lapangan']),
                'status'                  => $statusPinjam,
                'catatan_inventaris'      => $statusPinjam === 'dikembalikan' ? 'Kondisi barang kembali dengan lengkap dan mulus.' : null,
                'alasan_batal'            => $alasanBatal,
                'created_at'              => $tglPinjam,
                'updated_at'              => now(),
            ]);

            $itemPinjam = $faker->randomElements($peralatanIds, $faker->numberBetween(1, 3));
            foreach ($itemPinjam as $alatId) {
                DB::table('peminjaman_item')->insert([
                    'id_peminjaman' => $idPeminjaman,
                    'id_peralatan'  => $alatId,
                    'jumlah'       => $faker->numberBetween(1, 2),
                ]);
            }
        }
    }
}