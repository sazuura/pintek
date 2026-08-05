<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
    }
}
