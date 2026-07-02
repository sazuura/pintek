<?php

namespace Tests\Unit;

use App\Models\Absensi;
use Tests\TestCase;

/**
 * Test helper status dan accessor badge di Model Absensi.
 * Tidak perlu DB — pakai instance langsung.
 */
class AbsensiModelTest extends TestCase
{
    private function buat(string $status): Absensi
    {
        $a = new Absensi();
        $a->status = $status;
        return $a;
    }

    /** @test */
    public function is_pending_benar(): void
    {
        $this->assertTrue($this->buat('pending')->isPending());
        $this->assertFalse($this->buat('hadir')->isPending());
    }

    /** @test */
    public function is_hadir_benar(): void
    {
        $this->assertTrue($this->buat('hadir')->isHadir());
        $this->assertFalse($this->buat('pending')->isHadir());
    }

    /** @test */
    public function is_final_benar_untuk_status_yang_tidak_bisa_diubah(): void
    {
        foreach (['izin_disetujui', 'sakit_disetujui', 'alpha', 'ditolak'] as $status) {
            $this->assertTrue($this->buat($status)->isFinal(), "Gagal untuk status: $status");
        }
    }

    /** @test */
    public function is_final_salah_untuk_status_yang_masih_bisa_diubah(): void
    {
        foreach (['pending', 'hadir', 'izin', 'sakit'] as $status) {
            $this->assertFalse($this->buat($status)->isFinal(), "Gagal untuk status: $status");
        }
    }

    /** @test */
    public function badge_mengembalikan_class_dan_label_yang_benar(): void
    {
        $cases = [
            'hadir'           => ['badge-active',   'Hadir'],
            'pending'         => ['badge-warning',  'Pending'],
            'izin'            => ['badge-info',     'Izin (Proses)'],
            'izin_disetujui'  => ['badge-info',     'Izin'],
            'sakit'           => ['badge-purple',   'Sakit (Proses)'],
            'sakit_disetujui' => ['badge-purple',   'Sakit'],
            'alpha'           => ['badge-danger',   'Alpha'],
            'ditolak'         => ['badge-inactive', 'Ditolak'],
        ];

        foreach ($cases as $status => [$class, $label]) {
            $badge = $this->buat($status)->badge;
            $this->assertEquals($class, $badge['class'],  "Class salah untuk status: $status");
            $this->assertEquals($label, $badge['label'], "Label salah untuk status: $status");
        }
    }
}
