<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JadwalDiubahMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $namaOperator,
        public string $tanggal,
        public string $waktuMulai,
        public string $waktuSelesai,
        public string $judulKegiatan,
        public string $platform,
        public string $keterangan,
        public array $daftarPeralatan = [],
        public ?string $zoomPassword = null,
        public ?string $lokasiFisik = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Jadwal Rapat Diubah: {$this->judulKegiatan}");
    }

    public function content(): Content
    {
        return new Content(view: 'mails.jadwal-diubah');
    }
}
