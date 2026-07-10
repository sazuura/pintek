<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService
 * Mengirim pesan WhatsApp via Fonnte API.
 * Dipakai oleh dua alur:
 *   1. Admin buat jadwal  → notif ke operator yang ditugaskan
 *   2. Operator ajukan peminjaman → notif ke inventaris per gedung
 *
 */
class WhatsAppService
{
    private string $token;
    public function __construct()
    {
        $this->token = config('services.fonnte.token', '');
    }
    public function kirim(string $nomor, string $pesan): bool
    {
        if (empty($this->token)) {
            Log::warning('WhatsAppService: FONNTE_TOKEN kosong, pesan tidak terkirim.', [
                'nomor' => $nomor,
            ]);
            return false;
        }
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'target'  => $nomor,
                    'message' => $pesan,
                ],
                CURLOPT_HTTPHEADER => ['Authorization: ' . $this->token],
                CURLOPT_TIMEOUT    => 10,
            ]);
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error    = curl_error($curl);
            curl_close($curl);

            if ($error) {
                Log::error('WhatsAppService: cURL error (koneksi gagal/timeout).', [
                    'nomor' => $nomor,
                    'error' => $error,
                ]);
                return false;
            }

            // Fonnte tetap balas HTTP 200 walau pesannya sendiri ditolak (nomor tidak
            // valid, kuota habis, dsb) - status keberhasilan yang SEBENARNYA ada di
            // field "status" pada body JSON, bukan cuma dari sukses/tidaknya koneksi.
            $data = json_decode((string) $response, true);
            $statusApi = is_array($data) ? ($data['status'] ?? null) : null;

            if ($statusApi === false) {
                Log::error('WhatsAppService: Fonnte menolak pesan.', [
                    'nomor'     => $nomor,
                    'http_code' => $httpCode,
                    'alasan'    => $data['reason'] ?? $data['detail'] ?? 'tidak diketahui',
                    'response'  => $response,
                ]);
                return false;
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                Log::error('WhatsAppService: Fonnte membalas status HTTP non-2xx.', [
                    'nomor'     => $nomor,
                    'http_code' => $httpCode,
                    'response'  => $response,
                ]);
                return false;
            }

            Log::info('WhatsAppService: pesan terkirim.', [
                'nomor'    => $nomor,
                'response' => $response,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsAppService: exception.', [
                'nomor'   => $nomor,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
    public function templateJadwalBaru(
        string $namaOperator,
        string $tanggal,
        string $waktuMulai,
        string $waktuSelesai,
        string $judulKegiatan,
        string $platform,
        string $keterangan,
        ?string $daftarPeralatan = null,
        ?string $zoomPassword = null
    ): string {
        $pesan = "📢 *JADWAL RAPAT BARU*\n\n"
            . "Halo *{$namaOperator}*,\n"
            . "Anda ditugaskan untuk menangani rapat *{$judulKegiatan}* berikut:\n\n"
            . "📅 Tanggal : {$tanggal}\n"
            . "⏰ Waktu   : {$waktuMulai} - {$waktuSelesai} WIB\n"
            . "💻 Platform: {$platform}\n"
            . "📌 Keterangan: {$keterangan}\n";

        if ($zoomPassword) {
            $pesan .= "🔑 Password: {$zoomPassword}\n";
        }
        if ($daftarPeralatan) {
            $pesan .= "🧰 Peralatan yang Harus Dipinjam:\n{$daftarPeralatan}\n";
        }

        return $pesan . "\nHarap cek sistem untuk detail lengkap dan konfirmasi kehadiran Anda.\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
    }

    public function templateJadwalDiubah(
        string $namaOperator,
        string $tanggal,
        string $waktuMulai,
        string $waktuSelesai,
        string $judulKegiatan,
        string $platform,
        string $keterangan,
        ?string $daftarPeralatan = null,
        ?string $zoomPassword = null
    ): string {
        $pesan = "✏️ *JADWAL RAPAT DIUBAH*\n\n"
            . "Halo *{$namaOperator}*,\n"
            . "Detail rapat *{$judulKegiatan}* yang Anda tangani telah diperbarui:\n\n"
            . "📅 Tanggal : {$tanggal}\n"
            . "⏰ Waktu   : {$waktuMulai} - {$waktuSelesai} WIB\n"
            . "💻 Platform: {$platform}\n"
            . "📌 Keterangan: {$keterangan}\n";

        if ($zoomPassword) {
            $pesan .= "🔑 Password: {$zoomPassword}\n";
        }
        if ($daftarPeralatan) {
            $pesan .= "🧰 Peralatan yang Harus Dipinjam:\n{$daftarPeralatan}\n";
        }

        return $pesan . "\nHarap cek sistem untuk detail terbaru.\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
    }

    public function templateJadwalDibatalkan(
        string $namaOperator,
        string $tanggal,
        string $waktuMulai,
        string $waktuSelesai,
        string $judulKegiatan,
        string $platform,
        string $alasan,
        string $keterangan 
    ): string {
        return "📢 *PEMBATALAN TUGAS PENANGANAN RAPAT*\n\n"
            . "Halo *{$namaOperator}*,\n"
            . "Agenda rapat *{$judulKegiatan}* berikut yang sebelumnya ditugaskan kepada Anda telah *DIBATALKAN*:\n\n"
            . "📌 *Detail Agenda:*\n"
            . "   - Tanggal: {$tanggal}\n"
            . "   - Waktu: {$waktuMulai} - {$waktuSelesai} WIB\n"
            . "   - Platform: {$platform}\n"
            . "   - Keterangan: {$keterangan}\n\n"
            . "⚠️ *Alasan Pembatalan:*\n"
            . "   \"{$alasan}\"\n\n"
            . "Terima kasih atas perhatian Anda.\n\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
    }

    /**
     * @param  string       $namaInventaris   Nama petugas inventaris gedung tersebut
     * @param  string       $namaOperator     Nama operator yang mengajukan
     * @param  string       $gedung           Gedung yang peralatannya dipinjam
     * @param  string       $tanggalPinjam    Tanggal mulai pinjam
     * @param  string       $tanggalKembali   Rencana tanggal kembali
     * @param  string       $keperluan        Keperluan peminjaman
     * @param  string       $daftarPeralatan  Daftar peralatan dari gedung ini (sudah diformat)
     * @param  string|null  $terkaitJadwal    Judul + tanggal rapat terkait, kalau peminjaman ini dikaitkan ke jadwal
     */
    public function templatePeminjamanBaru(
        string $namaInventaris,
        string $namaOperator,
        string $gedung,
        string $tanggalPinjam,
        string $tanggalKembali,
        string $keperluan,
        string $daftarPeralatan,
        ?string $terkaitJadwal = null
    ): string {
        $baris = "📦 *PENGAJUAN PEMINJAMAN PERALATAN*\n\n"
            . "Halo *{$namaInventaris}*,\n"
            . "Ada pengajuan peminjaman peralatan dari *{$gedung}*:\n\n"
            . "👤 Pemohon  : {$namaOperator}\n"
            . "📅 Tanggal  : {$tanggalPinjam} - {$tanggalKembali}\n"
            . "📋 Keperluan: {$keperluan}\n";
        if ($terkaitJadwal) {
            $baris .= "🗓️ Terkait Rapat: {$terkaitJadwal}\n";
        }
        $baris .= "\n*Peralatan yang dipinjam:*\n{$daftarPeralatan}\n\n"
            . "Silakan login ke sistem untuk menyetujui atau menolak pengajuan ini.\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
        return $baris;
    }

    public function templatePeminjamanDiubah(
        string $namaInventaris,
        string $namaOperator,
        string $gedung,
        string $tanggalPinjam,
        string $tanggalKembali,
        string $keperluan,
        string $daftarPeralatan,
        ?string $terkaitJadwal = null
    ): string {
        $baris = "✏️ *PENGAJUAN PEMINJAMAN DIUBAH*\n\n"
            . "Halo *{$namaInventaris}*,\n"
            . "Pengajuan peminjaman peralatan dari *{$gedung}* berikut telah diperbarui oleh pemohon:\n\n"
            . "👤 Pemohon  : {$namaOperator}\n"
            . "📅 Tanggal  : {$tanggalPinjam} - {$tanggalKembali}\n"
            . "📋 Keperluan: {$keperluan}\n";
        if ($terkaitJadwal) {
            $baris .= "🗓️ Terkait Rapat: {$terkaitJadwal}\n";
        }
        $baris .= "\n*Peralatan yang dipinjam (terbaru):*\n{$daftarPeralatan}\n\n"
            . "Silakan login ke sistem untuk meninjau perubahan ini.\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
        return $baris;
    }

    public function templateOtpLupaPassword(string $namaUser, string $kode): string
    {
        return "🔐 *RESET KATA SANDI*\n\n"
            . "Halo *{$namaUser}*,\n"
            . "Kode verifikasi untuk atur ulang kata sandi akun kamu:\n\n"
            . "*{$kode}*\n\n"
            . "Kode ini berlaku 10 menit. Jangan berikan kode ini ke siapa pun, termasuk pihak yang mengaku dari Diskominfotik.\n"
            . "Kalau kamu tidak merasa meminta ini, abaikan pesan ini.\n\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
    }

    public function templatePeminjamanDibatalkan(
        string $namaInventaris,
        string $namaOperator,
        string $gedung,
        string $tanggalPinjam,
        string $tanggalKembali,
        string $keperluan,
        string $daftarPeralatan,
        string $alasan
    ): string {
        return "📦 *PEMBATALAN PENGAJUAN PEMINJAMAN PERALATAN*\n\n"
            . "Halo *{$namaInventaris}*,\n"
            . "Pengajuan peminjaman peralatan berikut telah *DIBATALKAN* oleh pemohon:\n\n"
            . "👤 *Data Pemohon:*\n"
            . "   - Nama: {$namaOperator}\n"
            . "   - Keperluan: {$keperluan}\n\n"
            . "📅 *Waktu Rencana Peminjaman:*\n"
            . "   - Tanggal: {$tanggalPinjam} s.d. {$tanggalKembali}\n\n"
            . "📦 *Daftar Peralatan Terkait:*\n"
            . "{$daftarPeralatan}\n\n"
            . "⚠️ *Alasan Pembatalan:*\n"
            . "   \"{$alasan}\"\n\n"
            . "Sistem telah otomatis memperbarui status pengajuan dan silakan meninjau stok peralatan terkait.\n\n"
            . "_Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik._";
    }
}
