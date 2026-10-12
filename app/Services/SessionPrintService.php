<?php

namespace App\Services;

use App\Mail\SessionPrintMail;
use App\Models\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Mengirim paket foto sesi lewat email dan/atau perintah printer.
 *
 * Dipakai bersama oleh Api\PhotoboothController dan SessionController (web)
 * supaya perilakunya sama, dan hasilnya JUJUR: "email terkirim" hanya bila
 * email benar-benar dikirim lewat mailer sungguhan (bukan driver log/array).
 */
class SessionPrintService
{
    /** Driver mail yang tidak mengirim email sungguhan. */
    private const FAKE_MAILERS = ['log', 'array'];

    /**
     * @return array{
     *   email_sent: bool,
     *   print_dispatched: bool,
     *   email_error: ?string,
     *   print_error: ?string,
     *   message: string
     * }
     */
    public function dispatch(Session $session, string $email): array
    {
        $session->loadMissing('media');
        $zipPath = $this->createZip($session);

        try {
            [$emailSent, $emailError] = $this->sendEmail($session, $email, $zipPath);
            [$printed, $printError] = $this->runPrinter($zipPath);
        } finally {
            @unlink($zipPath);
        }

        if ($emailSent) {
            $session->update(['email' => $email]);
        }

        return [
            'email_sent' => $emailSent,
            'print_dispatched' => $printed,
            'email_error' => $emailError,
            'print_error' => $printError,
            'message' => $this->message($emailSent, $printed, $emailError, $printError),
        ];
    }

    private function sendEmail(Session $session, string $email, string $zipPath): array
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, self::FAKE_MAILERS, true)) {
            return [false, "Email tidak dikirim: MAIL_MAILER={$mailer} hanya menulis ke log. Isi MAIL_MAILER=smtp (beserta MAIL_HOST dll) di .env."];
        }

        try {
            Mail::to($email)->send(new SessionPrintMail($session, $zipPath));

            return [true, null];
        } catch (Throwable $e) {
            report($e);

            return [false, 'Gagal mengirim email: ' . $e->getMessage()];
        }
    }

    private function runPrinter(string $zipPath): array
    {
        $template = config('photobooth.printer_command');

        if (!$template) {
            return [false, 'Printer belum dikonfigurasi (PHOTOBOOTH_PRINTER_COMMAND kosong).'];
        }

        $command = str_replace('{file}', escapeshellarg($zipPath), $template);

        try {
            exec($command, $output, $status);
        } catch (Throwable $e) {
            report($e);

            return [false, 'Perintah printer gagal dijalankan: ' . $e->getMessage()];
        }

        return $status === 0
            ? [true, null]
            : [false, "Perintah printer berakhir dengan kode {$status}."];
    }

    private function message(bool $emailSent, bool $printed, ?string $emailError, ?string $printError): string
    {
        if ($emailSent && $printed) {
            return 'Email terkirim dan perintah print dijalankan.';
        }
        if ($emailSent) {
            return 'Email terkirim. ' . $printError;
        }
        if ($printed) {
            return 'Perintah print dijalankan. ' . $emailError;
        }

        return trim($emailError . ' ' . $printError);
    }

    private function createZip(Session $session): string
    {
        $path = storage_path("app/temp/session-{$session->session_code}.zip");
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create print package.');
        }

        foreach ($session->media as $media) {
            $storedPath = ltrim(preg_replace('#^/?storage/#', '', $media->path), '/');
            if (Storage::disk('public')->exists($storedPath)) {
                $zip->addFromString($media->file_name ?? basename($storedPath), Storage::disk('public')->get($storedPath));
            }
        }
        $zip->close();

        return $path;
    }
}
