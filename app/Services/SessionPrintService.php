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
     * @param string|null $email        null = tidak kirim email.
     * @param bool        $print        false = tidak menjalankan perintah printer.
     * @param bool        $markPrinted  false = jangan catat sebagai "dicetak"
     *                                  (kiosk sudah mencatatnya sendiri).
     *
     * @return array{
     *   email_sent: bool,
     *   print_dispatched: bool,
     *   email_error: ?string,
     *   print_error: ?string,
     *   message: string
     * }
     */
    public function dispatch(Session $session, ?string $email, bool $print = true, bool $markPrinted = true): array
    {
        $session->loadMissing('media');
        $zipPath = $this->createZip($session);

        $emailSent = false;
        $emailError = null;
        $printed = false;
        $printError = null;

        try {
            if ($email) {
                [$emailSent, $emailError] = $this->sendEmail($session, $email, $zipPath);
            }
            if ($print) {
                [$printed, $printError] = $this->runPrinter($zipPath, $this->printTarget($session));
            }
        } finally {
            @unlink($zipPath);
        }

        if ($emailSent) {
            $session->update(['email' => $email]);
        }

        if ($printed && $markPrinted) {
            $session->markPrinted();
        }

        return [
            'email_sent' => $emailSent,
            'print_dispatched' => $printed,
            'email_error' => $emailError,
            'print_error' => $printError,
            'message' => $this->message($emailSent, $printed, $emailError, $printError),
        ];
    }

    /** Path lokal gambar mock strip (hasil akhir) sesi ini, bila ada. */
    public function stripFile(Session $session): ?string
    {
        $strip = $session->media->where('type', 'strip')->sortByDesc('id')->first();

        return $strip ? $this->localPath($strip->path) : null;
    }

    /**
     * File yang dikirim ke printer lewat placeholder {strip}: mock strip,
     * atau foto pertama bila mock belum ada.
     */
    private function printTarget(Session $session): ?string
    {
        if ($path = $this->stripFile($session)) {
            return $path;
        }

        $first = $session->media->where('type', 'photo')->sortBy('id')->first();

        return $first ? $this->localPath($first->path) : null;
    }

    private function localPath(?string $mediaPath): ?string
    {
        $rel = ltrim(preg_replace('#^/?storage/#', '', (string) $mediaPath), '/');

        return $rel !== '' && Storage::disk('public')->exists($rel)
            ? Storage::disk('public')->path($rel)
            : null;
    }

    private function sendEmail(Session $session, string $email, string $zipPath): array
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, self::FAKE_MAILERS, true)) {
            return [false, "Email tidak dikirim: MAIL_MAILER={$mailer} hanya menulis ke log. Isi MAIL_MAILER=smtp (beserta MAIL_HOST dll) di .env."];
        }

        try {
            Mail::to($email)->send(new SessionPrintMail($session, $zipPath, $this->stripFile($session)));

            return [true, null];
        } catch (Throwable $e) {
            report($e);

            return [false, 'Gagal mengirim email: ' . $e->getMessage()];
        }
    }

    private function runPrinter(string $zipPath, ?string $target = null): array
    {
        $template = config('photobooth.printer_command');

        if (!$template) {
            return [false, 'Printer belum dikonfigurasi (PHOTOBOOTH_PRINTER_COMMAND kosong).'];
        }

        // {file} = ZIP semua foto, {strip} = gambar mock strip (atau foto pertama).
        $command = str_replace(
            ['{file}', '{strip}'],
            [escapeshellarg($zipPath), escapeshellarg($target ?? $zipPath)],
            $template
        );

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

        $parts = [];
        $parts[] = $emailSent ? 'Email terkirim.' : $emailError;
        $parts[] = $printed ? 'Perintah print dijalankan.' : $printError;

        return trim(implode(' ', array_filter($parts)));
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
