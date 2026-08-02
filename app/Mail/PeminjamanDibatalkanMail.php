<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PeminjamanDibatalkanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $namaInventaris,
        public string $namaOperator,
        public string $gedung,
        public string $tanggalPinjam,
        public string $tanggalKembali,
        public string $keperluan,
        public array $peralatanPerGedung,
        public string $alasan,
        public ?string $terkaitJadwal = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pengajuan Peminjaman Dibatalkan dari {$this->gedung}");
    }

    public function content(): Content
    {
        return new Content(view: 'mails.peminjaman-dibatalkan');
    }
}
