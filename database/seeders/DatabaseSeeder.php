<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); 
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('peralatan')->truncate();
        DB::table('penjadwalan')->truncate();
        DB::table('peminjaman')->truncate();
        DB::table('peminjaman_item')->truncate();
        DB::table('jadwal_operator')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. GENERATE USERS (7 Orang)
        $users = [];

        // 1 Admin
        $users[] = [
            'id_user'       => 'USR-' . $faker->unique()->numerify('#####'),
            'nama_user'     => 'Admin Sazuura',
            'jenis_kelamin' => 'L',
            'alamat'        => $faker->address(),
            'nohp'          => '0812' . $faker->numerify('########'),
            'email'         => 'admin@diskominfotik.go.id',
            'password'      => Hash::make('password'),
            'role'          => 'admin',
            'status'        => 'active',
        ];

        // 1 Inventaris Utama (Sekarang menghandle semua gedung, kolom gedung di-set null/global)
        $users[] = [
            'id_user'       => 'USR-' . $faker->unique()->numerify('#####'),
            'nama_user'     => 'Fadhil (Staf Inventaris Utama)',
            'jenis_kelamin' => 'L',
            'alamat'        => $faker->address(),
            'nohp'          => '0857' . $faker->numerify('########'),
            'email'         => 'inventaris@diskominfotik.go.id',
            'password'      => Hash::make('password'),
            'role'          => 'inventaris',
            'status'        => 'active',
        ];

        // 5 Operator
        for ($i = 1; $i <= 5; $i++) {
            $jenisKelamin = $faker->randomElement(['L', 'P']);
            $users[] = [
                'id_user'       => 'USR-' . $faker->unique()->numerify('#####'),
                'nama_user'     => ($jenisKelamin === 'L' ? $faker->firstNameMale() : $faker->firstNameFemale()) . ' ' . $faker->lastName() . ' (Operator)',
                'jenis_kelamin' => $jenisKelamin,
                'alamat'        => $faker->address(),
                'nohp'          => '0813' . $faker->numerify('########'),
                'email'         => 'operator' . $i . '@diskominfotik.go.id',
                'password'      => Hash::make('password'),
                'role'          => 'operator',
                'status'        => $faker->randomElement(['active', 'active', 'active', 'inactive']),
            ];
        }

        DB::table('users')->insert($users);

        // Ambil list ID user berdasarkan role untuk relasi ke tabel lain
        $allUserIds     = DB::table('users')->pluck('id_user')->toArray();
        $operatorIds    = DB::table('users')->where('role', 'operator')->pluck('id_user')->toArray();
        $peminjamIds    = DB::table('users')->whereIn('role', ['operator', 'admin'])->pluck('id_user')->toArray();

        // 2. GENERATE PERALATAN (3 Gedung x 15 Barang = 45 Barang Kantor Riil)
        $gedungList = ['Gedung A (Kominfo)', 'Gedung B (Persandian)', 'Gedung C (TIK)'];
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

        // 3. GENERATE JADWAL (realistis: 1-2 rapat per minggu, 2 bulan ke belakang s/d 2 bulan ke depan)
        $jadwalPerOperator = [];
        $judulKegiatan = [
            'Rapat Koordinasi Evaluasi SPBE', 'Bimtek Pengelolaan Website Desa',
            'Sosialisasi Cyber Security Awareness', 'Focus Group Discussion Smart City',
            'Pelatihan Jurnalistik & Kehumasan', 'Rapat Integrasi Satu Data KBB',
            'Workshop Pengembangan Aplikasi Internal', 'Audiensi Implementasi E-Office'
        ];

        $awalRentang  = Carbon::now()->subMonths(2)->startOfWeek(Carbon::MONDAY);
        $akhirRentang = Carbon::now()->addMonths(2)->endOfWeek(Carbon::SUNDAY);

        $cursorMinggu = $awalRentang->copy();
        while ($cursorMinggu->lte($akhirRentang)) {
            // 1-2 rapat per minggu, ditaruh di hari kerja (Senin-Jumat) acak agar tidak selalu di hari yang sama
            $hariKerja = [0, 1, 2, 3, 4];
            shuffle($hariKerja);
            $hariTerpilih = array_slice($hariKerja, 0, $faker->numberBetween(1, 2));

            foreach ($hariTerpilih as $offsetHari) {
                $tanggal = $cursorMinggu->copy()->addDays($offsetHari)->format('Y-m-d');

                $idJadwal    = 'JDW-' . $faker->unique()->numerify('#####');
                $status      = $faker->randomElement(['selesai', 'selesai', 'selesai', 'dibatalkan']);
                $alasanBatal = ($status === 'dibatalkan') ? $faker->randomElement(['Kuorum tidak terpenuhi', 'Jadwal bentrok dengan pimpinan', 'Teknis jaringan bermasalah']) : null;

                $jamMulai   = $faker->randomElement(['09:00:00', '10:00:00', '13:30:00']);
                $jamSelesai = date('H:i:s', strtotime($jamMulai) + (3600 * $faker->numberBetween(1, 3)));

                DB::table('penjadwalan')->insert([
                    'id_penjadwalan' => $idJadwal,
                    'judul_kegiatan' => $faker->randomElement($judulKegiatan) . ' Angkatan ' . $faker->numberBetween(1, 5),
                    'tanggal'        => $tanggal,
                    'waktu_mulai'    => $jamMulai,
                    'waktu_selesai'  => $jamSelesai,
                    'platform'       => $faker->randomElement(['Offline', 'Zoom Cloud Meetings', 'Google Meet']),
                    'keterangan'     => $faker->sentence(),
                    'status'         => $status,
                    'alasan_batal'   => $alasanBatal,
                ]);

                $operatorTerpilih = $faker->randomElements($operatorIds, $faker->numberBetween(1, 3));
                foreach ($operatorTerpilih as $idOperator) {
                    DB::table('jadwal_operator')->insert([
                        'id_penjadwalan' => $idJadwal,
                        'id_user'        => $idOperator,
                    ]);
                    // Simpan buat referensi opsional saat generate peminjaman (Bagian B)
                    $jadwalPerOperator[$idOperator][] = $idJadwal;
                }
            }

            $cursorMinggu->addWeek();
        }

        // 4. GENERATE PEMINJAMAN (100 Data)
        for ($i = 1; $i <= 100; $i++) {
            $statusPinjam  = $faker->randomElement(['diajukan', 'disetujui', 'ditolak', 'dikembalikan', 'dibatalkan']);
            $tglPinjam     = $faker->dateTimeBetween('-2 months', '+2 weeks');
            $tglKembaliRcn = clone $tglPinjam;
            $tglKembaliRcn->modify('+' . $faker->numberBetween(1, 5) . ' days');
            
            $tglKembaliAkt = ($statusPinjam === 'dikembalikan') ? clone $tglKembaliRcn : null;
            $alasanBatal   = ($statusPinjam === 'dibatalkan' || $statusPinjam === 'ditolak') ? $faker->sentence() : null;

            // Generate string ID manual
            $idPeminjaman = 'PMJ-2026-' . sprintf('%04d', $i);
            $idPeminjam   = $faker->randomElement($peminjamIds);

            // 30% peminjaman dikaitkan ke salah satu jadwal milik peminjam (kalau ada)
            $idPenjadwalanTerkait = null;
            if (!empty($jadwalPerOperator[$idPeminjam]) && $faker->boolean(30)) {
                $idPenjadwalanTerkait = $faker->randomElement($jadwalPerOperator[$idPeminjam]);
            }

            DB::table('peminjaman')->insert([
                'id_peminjaman'           => $idPeminjaman,
                'id_user'                 => $idPeminjam,
                'id_penjadwalan'          => $idPenjadwalanTerkait,
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