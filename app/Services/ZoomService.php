<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    public function buatMeeting(string $akun, array $data): ?array
    {
        $token = $this->getAccessToken($akun);
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($this->userUrl($akun) . '/meetings', [
                    'topic'      => $data['topic'],
                    'type'       => 2,
                    'start_time' => $data['start_time'],
                    'duration'   => $data['duration'],
                    'timezone'   => 'Asia/Jakarta',
                    'settings'   => [
                        'join_before_host' => true,
                        'waiting_room'      => false,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('ZoomService: gagal membuat meeting.', [
                    'akun'   => $akun,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $body = $response->json();
            return [
                'meeting_id' => (string) ($body['id'] ?? ''),
                'join_url'   => $body['join_url'] ?? null,
                'password'   => $body['password'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('ZoomService: exception saat membuat meeting.', [
                'akun'    => $akun,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function updateMeeting(string $akun, string $meetingId, array $data): bool
    {
        $token = $this->getAccessToken($akun);
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->patch("https://api.zoom.us/v2/meetings/{$meetingId}", array_filter([
                    'topic'      => $data['topic']      ?? null,
                    'start_time' => $data['start_time'] ?? null,
                    'duration'   => $data['duration']   ?? null,
                    'timezone'   => 'Asia/Jakarta',
                ]));

            if ($response->failed()) {
                Log::error('ZoomService: gagal update meeting.', [
                    'akun'       => $akun,
                    'meeting_id' => $meetingId,
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('ZoomService: exception saat update meeting.', [
                'akun'       => $akun,
                'meeting_id' => $meetingId,
                'message'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function hapusMeeting(string $akun, string $meetingId): bool
    {
        $token = $this->getAccessToken($akun);
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->delete("https://api.zoom.us/v2/meetings/{$meetingId}");

            if ($response->successful() || $response->status() === 404) {
                return true;
            }

            Log::error('ZoomService: gagal menghapus meeting.', [
                'akun'       => $akun,
                'meeting_id' => $meetingId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('ZoomService: exception saat menghapus meeting.', [
                'akun'       => $akun,
                'meeting_id' => $meetingId,
                'message'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function userUrl(string $akun): string
    {
        $userId = config("services.zoom.{$akun}.user_id", 'me');
        return 'https://api.zoom.us/v2/users/' . $userId;
    }

    private function getAccessToken(string $akun): ?string
    {
        $cacheKey = "zoom_token_{$akun}";
        $cached   = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $accountId    = config("services.zoom.{$akun}.account_id", '');
        $clientId     = config("services.zoom.{$akun}.client_id", '');
        $clientSecret = config("services.zoom.{$akun}.client_secret", '');

        if (empty($accountId) || empty($clientId) || empty($clientSecret)) {
            Log::warning('ZoomService: kredensial akun kosong, token tidak bisa diambil.', ['akun' => $akun]);
            return null;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(10)
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId,
                ]);

            if ($response->failed()) {
                Log::error('ZoomService: gagal mengambil access token.', [
                    'akun'   => $akun,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $token = $response->json('access_token');
            if ($token) {

                Cache::put($cacheKey, $token, now()->addMinutes(55));
            }
            return $token;
        } catch (\Throwable $e) {
            Log::error('ZoomService: exception saat mengambil access token.', [
                'akun'    => $akun,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
