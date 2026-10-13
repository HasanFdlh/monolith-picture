<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SessionPrintMail extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $zipPath;

    /** Path lokal gambar mock strip (ditampilkan langsung di isi email). */
    public $stripPath;

    public function __construct($session, $zipPath, $stripPath = null)
    {
        $this->session = $session;
        $this->zipPath = $zipPath;
        $this->stripPath = $stripPath;
    }

    public function build()
    {
        $fileName = basename($this->zipPath);

        return $this->subject('Your Photobooth Session: ' . ($this->session->session_code ?? ''))
            ->view('emails.session_print')
            ->with(['session' => $this->session, 'stripPath' => $this->stripPath])
            ->attach($this->zipPath, [
                'as' => $fileName,
                'mime' => 'application/zip',
            ]);
    }
}
