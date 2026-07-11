<?php

namespace Tests\Unit;

use App\Services\ZoomService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZoomServiceTest extends TestCase
{
    private ZoomService $zoom;

    protected function setUp(): void
    {
        parent::setUp();

        // Token di-cache per akun (lihat ZoomService::getAccessToken) - kalau tidak
        // di-flush, token dari test lain bisa "bocor" ke sini dan bikin mock Http::fake()
        // di test ini tidak pernah benar-benar kena (request token di-skip karena cache hit).
        Cache::flush();

        config([
            'services.zoom.akun_1' => [
                'account_id'    => 'acc1',
                'client_id'     => 'client1',
                'client_secret' => 'secret1',
                'user_id'       => 'me',
            ],
        ]);

        $this->zoom = new ZoomService();
    }

    /** @test */
    public function buat_meeting_sukses_mengembalikan_data_meeting(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/users/*/meetings' => Http::response([
                'id'       => 999888777,
                'join_url' => 'https://zoom.us/j/999888777',
                'password' => 'ab12cd',
            ], 201),
        ]);

        $hasil = $this->zoom->buatMeeting('akun_1', [
            'topic'      => 'Rapat Test',
            'start_time' => '2030-01-01T09:00:00',
            'duration'   => 60,
        ]);

        $this->assertNotNull($hasil);
        $this->assertSame('999888777', $hasil['meeting_id']);
        $this->assertSame('https://zoom.us/j/999888777', $hasil['join_url']);
        $this->assertSame('ab12cd', $hasil['password']);
    }

    /** @test */
    public function buat_meeting_gagal_kalau_token_gagal_diambil(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $hasil = $this->zoom->buatMeeting('akun_1', [
            'topic'      => 'Rapat Test',
            'start_time' => '2030-01-01T09:00:00',
            'duration'   => 60,
        ]);

        $this->assertNull($hasil);
    }

    /** @test */
    public function buat_meeting_gagal_kalau_api_meeting_error(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/users/*/meetings' => Http::response(['message' => 'Invalid'], 400),
        ]);

        $hasil = $this->zoom->buatMeeting('akun_1', [
            'topic'      => 'Rapat Test',
            'start_time' => '2030-01-01T09:00:00',
            'duration'   => 60,
        ]);

        $this->assertNull($hasil);
    }

    /** @test */
    public function buat_meeting_gagal_kalau_kredensial_kosong(): void
    {
        config(['services.zoom.akun_2' => [
            'account_id' => '', 'client_id' => '', 'client_secret' => '', 'user_id' => 'me',
        ]]);

        Http::fake(); // tidak boleh ada request sama sekali

        $hasil = $this->zoom->buatMeeting('akun_2', [
            'topic' => 'Rapat Test', 'start_time' => '2030-01-01T09:00:00', 'duration' => 60,
        ]);

        $this->assertNull($hasil);
        Http::assertNothingSent();
    }

    /** @test */
    public function token_di_cache_supaya_tidak_request_ulang(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/users/*/meetings' => Http::response([
                'id' => 1, 'join_url' => 'https://zoom.us/j/1', 'password' => 'x',
            ], 201),
        ]);

        $this->zoom->buatMeeting('akun_1', ['topic' => 'A', 'start_time' => '2030-01-01T09:00:00', 'duration' => 30]);
        $this->zoom->buatMeeting('akun_1', ['topic' => 'B', 'start_time' => '2030-01-02T09:00:00', 'duration' => 30]);

        Http::assertSentCount(3); // 1x token + 2x create meeting (bukan 2x token)
    }

    /** @test */
    public function update_meeting_sukses(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/meetings/*' => Http::response([], 204),
        ]);

        $ok = $this->zoom->updateMeeting('akun_1', 'MTG1', ['topic' => 'Baru', 'start_time' => '2030-01-01T10:00:00', 'duration' => 30]);

        $this->assertTrue($ok);
    }

    /** @test */
    public function update_meeting_gagal_kalau_api_error(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/meetings/*' => Http::response(['message' => 'Not found'], 404),
        ]);

        $ok = $this->zoom->updateMeeting('akun_1', 'MTG1', ['topic' => 'Baru']);

        $this->assertFalse($ok);
    }

    /** @test */
    public function hapus_meeting_sukses(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/meetings/*' => Http::response([], 204),
        ]);

        $ok = $this->zoom->hapusMeeting('akun_1', 'MTG1');

        $this->assertTrue($ok);
    }

    /** @test */
    public function hapus_meeting_dianggap_sukses_kalau_sudah_tidak_ada_404(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/meetings/*' => Http::response(['message' => 'Not found'], 404),
        ]);

        $ok = $this->zoom->hapusMeeting('akun_1', 'MTG-SUDAH-HILANG');

        $this->assertTrue($ok);
    }

    /** @test */
    public function hapus_meeting_gagal_kalau_error_lain(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'token123'], 200),
            'api.zoom.us/v2/meetings/*' => Http::response(['message' => 'Server error'], 500),
        ]);

        $ok = $this->zoom->hapusMeeting('akun_1', 'MTG1');

        $this->assertFalse($ok);
    }
}
