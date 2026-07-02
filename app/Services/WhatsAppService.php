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
            $error    = curl_error($curl);
            curl_close($curl);
            if ($error) {
                Log::error('WhatsAppService: cURL error.', ['nomor' => $nomor, 'error' => $error]);
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
        string $keterangan 
    ): string {
        return "📢 *JADWAL RAPAT BARU*\n\n"
            . "Halo *{$namaOperator}*,\n"
            . "Anda ditugaskan untuk menangani rapat *{$judulKegiatan}* berikut:\n\n"
            . "📅 Tanggal : {$tanggal}\n"
            . "⏰ Waktu   : {$waktuMulai} - {$waktuSelesai} WIB\n"
            . "💻 Platform: {$platform}\n"
            . "📌 Keterangan: {$keterangan}\n\n"
            . "Harap cek sistem untuk detail lengkap dan konfirmasi kehadiran Anda.\n"
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
     * @param  string  $namaInventaris   Nama petugas inventaris gedung tersebut
     * @param  string  $namaOperator     Nama operator yang mengajukan
     * @param  string  $gedung           Gedung yang peralatannya dipinjam
     * @param  string  $tanggalPinjam    Tanggal mulai pinjam
     * @param  string  $tanggalKembali   Rencana tanggal kembali
     * @param  string  $keperluan        Keperluan peminjaman
     * @param  string  $daftarPeralatan  Daftar peralatan dari gedung ini (sudah diformat)
     */
    public function templatePeminjamanBaru(
        string $namaInventaris,
        string $namaOperator,
        string $gedung,
        string $tanggalPinjam,
        string $tanggalKembali,
        string $keperluan,
        string $daftarPeralatan
    ): string {
        return "📦 *PENGAJUAN PEMINJAMAN PERALATAN*\n\n"
            . "Halo *{$namaInventaris}*,\n"
            . "Ada pengajuan peminjaman peralatan dari *{$gedung}*:\n\n"
            . "👤 Pemohon  : {$namaOperator}\n"
            . "📅 Tanggal  : {$tanggalPinjam} - {$tanggalKembali}\n"
            . "📋 Keperluan: {$keperluan}\n\n"
            . "*Peralatan yang dipinjam:*\n{$daftarPeralatan}\n\n"
            . "Silakan login ke sistem untuk menyetujui atau menolak pengajuan ini.\n"
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
