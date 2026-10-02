<?php
/**
 * MailService
 * SMTP mail helper (socket-based, no external libraries).
 * Ported from TicketingSystem2 and adapted to the unified config system.
 */

namespace App\Services;

class MailService
{
    private array $smtp;
    private string $logPath;

    public function __construct()
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $this->smtp = $appConfig['mail']['smtp'] ?? [];
        $this->logPath = STORAGE_PATH . '/logs/smtp.log';
    }

    /**
     * Send an email via SMTP.
     */
    public function send(string $toEmail, string $subject, string $body): bool
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '') {
            return false;
        }

        $host = (string) ($this->smtp['host'] ?? '');
        $port = (int) ($this->smtp['port'] ?? 587);
        $username = (string) ($this->smtp['username'] ?? '');
        $password = (string) ($this->smtp['password'] ?? '');
        $secure = (string) ($this->smtp['secure'] ?? 'tls');
        $fromEmail = (string) ($this->smtp['from_email'] ?? 'noreply@example.com');
        $fromName = (string) ($this->smtp['from_name'] ?? 'IT Systems');

        // Common mismatch auto-correct (STARTTLS vs implicit TLS)
        if ($secure === 'tls' && $port === 465) {
            $secure = 'ssl';
        }

        if ($host === '' || $fromEmail === '') {
            $this->log('SMTP config missing host/from_email', ['host' => $host, 'from_email' => $fromEmail]);
            return false;
        }

        $subject = str_replace(["\r", "\n"], '', $subject);

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=utf-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'To: ' . $toEmail,
            'X-Mailer: PHP/' . phpversion(),
        ];

        $transport = ($secure === 'ssl') ? 'ssl://' : '';
        $fp = @fsockopen($transport . $host, $port, $errno, $errstr, 15);
        if (!$fp) {
            $this->log('SMTP connect failed', ['errno' => $errno, 'errstr' => $errstr, 'host' => $host, 'port' => $port]);
            return false;
        }

        stream_set_timeout($fp, 20);
        stream_set_blocking($fp, true);

        $readResponse = function () use ($fp): array {
            $response = '';
            $code = null;
            $metaTimedOut = false;

            for ($i = 0; $i < 100 && !feof($fp); $i++) {
                $line = fgets($fp, 1024);
                if ($line === false) {
                    break;
                }
                $response .= $line;

                $meta = stream_get_meta_data($fp);
                if (!empty($meta['timed_out'])) {
                    $metaTimedOut = true;
                }

                if (preg_match('/^(\d{3})([\s-])/', $line, $m)) {
                    $code = (int) $m[1];
                    $sep = $m[2];
                    if ($sep === ' ') {
                        break;
                    }
                }
            }

            if ($metaTimedOut) {
                $this->log('SMTP read timeout', ['response' => $response]);
            }

            return ['code' => $code, 'raw' => $response];
        };

        $sendLine = function (string $cmd) use ($fp): void {
            fwrite($fp, $cmd . "\r\n");
        };

        $responses = [];

        // Greeting
        $resp = $readResponse();
        $responses[] = ['greeting' => $resp];
        if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
            fclose($fp);
            $this->log('SMTP greeting failed', ['response' => $resp, 'to' => $toEmail, 'subject' => $subject]);
            return false;
        }

        // EHLO
        $sendLine('EHLO localhost');
        $resp = $readResponse();
        $responses[] = ['ehlo' => $resp];
        if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
            fclose($fp);
            $this->log('EHLO failed', ['response' => $resp, 'to' => $toEmail, 'subject' => $subject]);
            return false;
        }

        // STARTTLS
        if ($secure === 'tls') {
            $sendLine('STARTTLS');
            $resp = $readResponse();
            $responses[] = ['starttls' => $resp];

            if (!($resp['code'] === 220)) {
                fclose($fp);
                $this->log('STARTTLS not accepted by server; refusing to enable crypto', ['starttls' => $resp, 'responses' => $responses]);
                return false;
            }

            $cryptoOk = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$cryptoOk) {
                fclose($fp);
                $this->log('STARTTLS enable failed', ['responses' => $responses, 'to' => $toEmail, 'subject' => $subject]);
                return false;
            }

            // Re-EHLO after TLS upgrade
            $sendLine('EHLO localhost');
            $resp = $readResponse();
            $responses[] = ['ehlo_after_tls' => $resp];
            if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
                fclose($fp);
                $this->log('EHLO after STARTTLS failed', ['response' => $resp, 'responses' => $responses]);
                return false;
            }
        }

        // AUTH LOGIN
        if ($username !== '' && $password !== '') {
            $sendLine('AUTH LOGIN');
            $resp = $readResponse();
            $responses[] = ['auth_login' => $resp];
            if (!in_array($resp['code'], [334], true)) {
                fclose($fp);
                $this->log('AUTH LOGIN failed', ['response' => $resp, 'responses' => $responses]);
                return false;
            }

            $sendLine(base64_encode($username));
            $resp = $readResponse();
            $responses[] = ['auth_user' => $resp];
            if (!in_array($resp['code'], [334], true)) {
                fclose($fp);
                $this->log('AUTH username not accepted', ['response' => $resp, 'responses' => $responses]);
                return false;
            }

            $sendLine(base64_encode($password));
            $resp = $readResponse();
            $responses[] = ['auth_pass' => $resp];
            if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
                fclose($fp);
                $this->log('AUTH password not accepted', ['response' => $resp, 'responses' => $responses]);
                return false;
            }
        }

        // MAIL FROM / RCPT TO
        $sendLine('MAIL FROM: <' . $fromEmail . '>');
        $resp = $readResponse();
        $responses[] = ['mail_from' => $resp];
        if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
            fclose($fp);
            $this->log('MAIL FROM failed', ['response' => $resp, 'responses' => $responses]);
            return false;
        }

        $sendLine('RCPT TO: <' . $toEmail . '>');
        $resp = $readResponse();
        $responses[] = ['rcpt_to' => $resp];
        if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
            fclose($fp);
            $this->log('RCPT TO failed', ['response' => $resp, 'responses' => $responses]);
            return false;
        }

        // DATA
        $sendLine('DATA');
        $resp = $readResponse();
        $responses[] = ['data' => $resp];
        if (!($resp['code'] === 354)) {
            fclose($fp);
            $this->log('DATA not accepted', ['response' => $resp, 'responses' => $responses]);
            return false;
        }

        // Body normalization + dot-stuffing
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);

        $bodyLines = explode("\r\n", $body);
        foreach ($bodyLines as &$line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
        }
        unset($line);
        $body = implode("\r\n", $bodyLines);

        $mime = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $mime = $mime . "\r\n.\r\n";

        fwrite($fp, $mime);
        $responses[] = ['data_end_sent' => true];

        $resp = $readResponse();
        $responses[] = ['data_end' => $resp];
        if (!($resp['code'] !== null && $resp['code'] >= 200 && $resp['code'] < 400)) {
            fclose($fp);
            $this->log('Message body not accepted (after DATA)', ['response' => $resp, 'responses' => $responses]);
            return false;
        }

        // QUIT
        $sendLine('QUIT');
        $resp = $readResponse();
        $responses[] = ['quit' => $resp];

        fclose($fp);
        return true;
    }

    /**
     * Append a log entry to the SMTP log file.
     */
    private function log(string $message, array $context = []): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $entry = [
            'time' => date('c'),
            'message' => $message,
            'context' => $context,
        ];
        @file_put_contents($this->logPath, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
}
