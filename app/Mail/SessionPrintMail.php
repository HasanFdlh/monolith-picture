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

    public function __construct($session, $zipPath)
    {
        $this->session = $session;
        $this->zipPath = $zipPath;
    }

    public function build()
    {
        $fileName = basename($this->zipPath);

        return $this->subject('Your Photobooth Session: ' . ($this->session->session_code ?? ''))
            ->view('emails.session_print')
            ->with(['session' => $this->session])
            ->attach($this->zipPath, [
                'as' => $fileName,
                'mime' => 'application/zip',
            ]);
    }
}
