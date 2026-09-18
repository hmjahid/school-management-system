<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\View;
use App\Models\Settings;

/**
 * Zero-dependency mailer. Reads email settings from the `settings` table:
 *   - driver = smtp   → speaks SMTP directly over a TLS/SSL socket (AUTH LOGIN).
 *   - driver = sendmail (or SMTP unconfigured) → falls back to PHP mail().
 * Used by Registration, Payment, License and Contact flows.
 */
class Mailer
{
    private const TIMEOUT = 20;

    private static function settings(): array
    {
        return [
            'driver'      => (string) Settings::get('email.driver', 'sendmail'),
            'host'        => trim((string) Settings::get('email.host', '')),
            'port'        => (int) Settings::get('email.port', 587),
            'username'    => (string) Settings::get('email.username', ''),
            'password'    => (string) Settings::get('email.password', ''),
            'encryption'  => (string) Settings::get('email.encryption', 'tls'),
            'from_address' => Settings::mailFromAddress(),
            'from_name'   => Settings::mailFromName(),
        ];
    }

    /**
     * Send a raw HTML email.
     *
     * @param array<string, mixed> $options overrides: from_address, from_name, reply_to
     */
    public static function send(string $to, string $subject, string $html, array $options = []): bool
    {
        $conf = self::settings();
        $fromAddress = (string) ($options['from_address'] ?? $conf['from_address']);
        $fromName = (string) ($options['from_name'] ?? $conf['from_name']);
        $replyTo = (string) ($options['reply_to'] ?? '');

        if ($conf['driver'] === 'smtp' && $conf['host'] !== '') {
            return self::smtpSend($conf, $to, $subject, $html, $fromAddress, $fromName, $replyTo);
        }

        return self::mailSend($to, $subject, $html, $fromAddress, $fromName, $replyTo);
    }

    /** Render an email template wrapped in the shared email layout. */
    public static function render(string $template, array $data = []): string
    {
        $data = array_merge([
            'siteUrl'  => $_ENV['APP_URL'] ?? '/',
            'brandName' => (string) Settings::get('site.name', 'Eskoofy'),
            'tagline'  => (string) Settings::get('site.tagline', ''),
            'year'     => (int) date('Y'),
        ], $data);

        // Admins can override any template body (and subject) from Admin → Email templates.
        $dbTemplate = self::dbTemplate($template);
        $contentHtml = '';
        if ($dbTemplate !== null && $dbTemplate['body'] !== '') {
            $body = $dbTemplate['body'];
            foreach ($data as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $body = str_replace('{' . $k . '}', (string) ($v ?? ''), $body);
                }
            }
            $contentHtml = $body;
        } else {
            $contentPath = View::resolve('emails.' . $template);
            if ($contentPath !== null && file_exists($contentPath)) {
                extract($data);
                ob_start();
                require $contentPath;
                $contentHtml = ob_get_clean();
            }
        }

        ob_start();
        $layoutPath = dirname(__DIR__, 2) . '/views/emails/layout.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        }

        return (string) ob_get_clean();
    }

    public static function sendView(string $to, string $subject, string $template, array $data = [], array $options = []): bool
    {
        $dbTemplate = self::dbTemplate($template);
        if ($dbTemplate !== null && trim((string) $dbTemplate['subject']) !== '') {
            $subject = (string) $dbTemplate['subject'];
        }

        return self::send($to, $subject, self::render($template, $data), $options);
    }

    /**
     * Return an admin-overridden template (subject/body) if one is stored.
     *
     * @return array{subject: string, body: string}|null
     */
    private static function dbTemplate(string $key): ?array
    {
        try {
            $row = Database::getInstance()->fetch(
                "SELECT subject, body, is_active FROM email_templates WHERE tkey = ? AND is_active = 1",
                [$key]
            );
        } catch (\Throwable) {
            return null;
        }
        if (!$row) {
            return null;
        }

        return [
            'subject' => (string) ($row['subject'] ?? ''),
            'body'    => (string) ($row['body'] ?? ''),
        ];
    }

    // ── sendmail / mail() path ─────────────────────────────────────────────
    private static function mailSend(string $to, string $subject, string $html, string $fromAddress, string $fromName, string $replyTo): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . self::encodeHeader($fromName) . ' <' . $fromAddress . '>',
        ];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $headers[] = 'X-Mailer: Eskoofy';

        return @mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            $html,
            implode("\r\n", $headers)
        );
    }

    // ── native SMTP path (no sockets extension gating; PHP ships it) ──────
    private static function smtpSend(array $conf, string $to, string $subject, string $html, string $fromAddress, string $fromName, string $replyTo): bool
    {
        $host = $conf['host'];
        $port = $conf['port'];
        $scheme = 'tcp://';
        if ($conf['encryption'] === 'ssl') {
            $scheme = 'ssl://';
        }

        $errno = 0;
        $errstr = '';
        $conn = @stream_socket_client(
            $scheme . $host . ':' . $port,
            $errno,
            $errstr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]])
        );
        if (!$conn) {
            return false;
        }
        stream_set_timeout($conn, self::TIMEOUT);

        if (!self::smtpRead($conn, '220')) {
            fclose($conn);
            return false;
        }

        if (!self::smtpCmd($conn, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '250') &&
            !self::smtpCmd($conn, 'HELO localhost', '250')) {
            fclose($conn);
            return false;
        }

        if ($conf['encryption'] === 'tls') {
            if (!self::smtpCmd($conn, 'STARTTLS', '220')) {
                fclose($conn);
                return false;
            }
            stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!self::smtpCmd($conn, 'EHLO localhost', '250')) {
                fclose($conn);
                return false;
            }
        }

        if ($conf['username'] !== '') {
            if (!self::smtpCmd($conn, 'AUTH LOGIN', '334') ||
                !self::smtpCmd($conn, base64_encode($conf['username']), '334') ||
                !self::smtpCmd($conn, base64_encode($conf['password']), '235')) {
                fclose($conn);
                return false;
            }
        }

        if (!self::smtpCmd($conn, "MAIL FROM:<{$fromAddress}>", '250') ||
            !self::smtpCmd($conn, "RCPT TO:<{$to}>", '250') ||
            !self::smtpCmd($conn, 'DATA', '354')) {
            fclose($conn);
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . self::encodeHeader($fromName) . ' <' . $fromAddress . '>',
        ];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'Date: ' . gmdate('D, d M Y H:i:s O');

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\r\n.\r\n", "\r\n..\r\n", (string) preg_replace('/\r\n|\r|\n/', "\r\n", $html)) . "\r\n";

        if (!self::smtpCmd($conn, $payload . '.', '250')) {
            fclose($conn);
            return false;
        }

        self::smtpCmd($conn, 'QUIT', '221');
        fclose($conn);

        return true;
    }

    private static function smtpCmd($conn, string $command, string $expected): bool
    {
        fwrite($conn, $command . "\r\n");

        $expectedCodes = array_filter(array_map('trim', explode('|', $expected)));
        foreach ($expectedCodes as $code) {
            $line = self::smtpRead($conn, $code);
            if ($line !== null) {
                return true;
            }
        }

        return false;
    }

    private static function smtpRead($conn, string $expectedCode): ?string
    {
        $response = fgets($conn);
        while ($response !== false && strlen($response) > 3 && $response[3] === '-') {
            $response = fgets($conn);
        }
        if ($response === false) {
            return null;
        }

        return str_starts_with($response, $expectedCode) ? trim($response) : null;
    }

    private static function encodeHeader(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/[\x80-\xff]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }

        return $value;
    }
}