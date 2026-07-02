<?php

namespace Tests\Unit;

use App\Models\Peralatan;
use Tests\TestCase;

/**
 * Test accessor/helper di Model Peralatan.
 * Tidak perlu DB — pakai instance langsung.
 */
class PeralatanModelTest extends TestCase
{
    private function buat(int $stok, int $rusak = 0, int $perbaikan = 0): Peralatan
    {
        $p = new Peralatan();
        $p->stok      = $stok;
        $p->rusak     = $rusak;
        $p->perbaikan = $perbaikan;
        return $p;
    }

    /** @test */
    public function stok_tersedia_dihitung_dengan_benar(): void
    {
        $this->assertEquals(3, $this->buat(stok: 5, rusak: 1, perbaikan: 1)->stok_tersedia);
    }

    /** @test */
    public function stok_tersedia_tidak_boleh_negatif(): void
    {
        $this->assertEquals(0, $this->buat(stok: 2, rusak: 2, perbaikan: 3)->stok_tersedia);
    }

    /** @test */
    public function status_label_tersedia_jika_stok_cukup(): void
    {
        $this->assertEquals('Tersedia', $this->buat(stok: 5)->statusLabel);
    }

    /** @test */
    public function status_label_hampir_habis_jika_stok_1_atau_2(): void
    {
        $this->assertEquals('Hampir Habis', $this->buat(stok: 2)->statusLabel);
        $this->assertEquals('Hampir Habis', $this->buat(stok: 1)->statusLabel);
    }

    /** @test */
    public function status_label_tidak_tersedia_jika_stok_0(): void
    {
        $this->assertEquals('Tidak Tersedia', $this->buat(stok: 0)->statusLabel);
    }

    /** @test */
    public function badge_class_sesuai_stok(): void
    {
        $this->assertEquals('badge-active',  $this->buat(stok: 5)->statusBadgeClass);
        $this->assertEquals('badge-warning', $this->buat(stok: 2)->statusBadgeClass);
        $this->assertEquals('badge-danger',  $this->buat(stok: 0)->statusBadgeClass);
    }
}
