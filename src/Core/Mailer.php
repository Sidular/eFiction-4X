<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * Lightweight mailer using PHP's built-in mail() and optional raw SMTP sockets.
 * PHPMailer is no longer required for upload-and-go deployments.
 */
class Mailer
{
    public function __construct(private readonly Config $config) {}

    public function send(string $to, string $subject, string $body, string $toName = ''): bool
    {
        $method = $this->config->get('mail.method', 'mail');

        if ($method === 'smtp') {
            $result = $this->sendSmtp($to, $toName, $subject, $body);
            if ($result) {
                return true;
            }
            // Fall back to PHP mail() if raw SMTP fails.
            error_log('Mailer: SMTP failed, falling back to PHP mail()');
        }

        return $this->sendMail($to, $toName, $subject, $body);
    }

    private function sendMail(string $to, string $toName, string $subject, string $body): bool
    {
        $from = $this->config->get('mail.from', 'noreply@example.com');
        $siteName = $this->config->get('site.title', 'eFiction');

        $displayTo = $toName !== '' ? "\"{$toName}\" <{$to}>" : $to;

        $headers = [
            'From' => "\"{$siteName}\" <{$from}>",
            'Reply-To' => $from,
            'X-Mailer' => 'eFiction/PHP',
            'MIME-Version' => '1.0',
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }
        $headerString .= "Content-Type: multipart/alternative; boundary=\"efiction-alt-boundary\"\r\n";

        $altBody = $this->buildPlainText($body);

        $message = "--efiction-alt-boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= $altBody . "\r\n\r\n";
        $message .= "--efiction-alt-boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $body . "\r\n\r\n";
        $message .= "--efiction-alt-boundary--";

        $result = mail($displayTo, $subject, $message, $headerString);

        if (!$result) {
            error_log('Mailer error: PHP mail() failed');
        }

        return $result;
    }

    private function sendSmtp(string $to, string $toName, string $subject, string $body): bool
    {
        $host = $this->config->get('mail.smtp.host', '');
        $port = (int) $this->config->get('mail.smtp.port', 587);
        $auth = (bool) $this->config->get('mail.smtp.auth', false);
        $username = $this->config->get('mail.smtp.username', '');
        $password = $this->config->get('mail.smtp.password', '');
        $secure = $this->config->get('mail.smtp.secure', 'tls');

        $from = $this->config->get('mail.from', 'noreply@example.com');
        $siteName = $this->config->get('site.title', 'eFiction');

        if ($host === '') {
            error_log('Mailer error: SMTP host not configured');
            return false;
        }

        $timeout = 30;
        $errorNumber = 0;
        $errorString = '';

        $address = $secure === 'ssl' ? 'ssl://' . $host : 'tcp://' . $host;
        $socket = @fsockopen($address, $port, $errorNumber, $errorString, $timeout);

        if (!$socket) {
            error_log("Mailer error: SMTP connection failed: {$errorNumber} {$errorString}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        if (!$this->smtpRead($socket, 220)) {
            fclose($socket);
            return false;
        }

        $helo = $_SERVER['SERVER_NAME'] ?? gethostname() ?? 'localhost';

        if (!$this->smtpCommand($socket, 'EHLO ' . $helo, 250)) {
            if (!$this->smtpCommand($socket, 'HELO ' . $helo, 250)) {
                fclose($socket);
                return false;
            }
        }

        if ($secure === 'tls') {
            if (!$this->smtpCommand($socket, 'STARTTLS', 220)) {
                fclose($socket);
                return false;
            }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                error_log('Mailer error: SMTP TLS handshake failed');
                fclose($socket);
                return false;
            }

            if (!$this->smtpCommand($socket, 'EHLO ' . $helo, 250)) {
                fclose($socket);
                return false;
            }
        }

        if ($auth) {
            if (!$this->smtpCommand($socket, 'AUTH LOGIN', 334)) {
                fclose($socket);
                return false;
            }
            if (!$this->smtpCommand($socket, base64_encode($username), 334)) {
                fclose($socket);
                return false;
            }
            if (!$this->smtpCommand($socket, base64_encode($password), 235)) {
                fclose($socket);
                return false;
            }
        }

        if (!$this->smtpCommand($socket, 'MAIL FROM:<' . $from . '>', 250)) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', 250)) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, 'DATA', 354)) {
            fclose($socket);
            return false;
        }

        $messageId = '<' . uniqid('efiction-', true) . '@' . $helo . '>';
        $altBody = $this->buildPlainText($body);

        $displayTo = $toName !== '' ? "\"{$toName}\" <{$to}>" : $to;

        $data = "Date: " . date('r') . "\r\n";
        $data .= "To: {$displayTo}\r\n";
        $data .= "From: \"{$siteName}\" <{$from}>\r\n";
        $data .= "Reply-To: {$from}\r\n";
        $data .= "Subject: {$subject}\r\n";
        $data .= "Message-ID: {$messageId}\r\n";
        $data .= "X-Mailer: eFiction/PHP\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "Content-Type: multipart/alternative; boundary=\"efiction-alt-boundary\"\r\n";
        $data .= "\r\n";
        $data .= "--efiction-alt-boundary\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "\r\n";
        $data .= $altBody . "\r\n";
        $data .= "\r\n";
        $data .= "--efiction-alt-boundary\r\n";
        $data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $data .= "\r\n";
        $data .= $body . "\r\n";
        $data .= "\r\n";
        $data .= "--efiction-alt-boundary--\r\n";

        $data = str_replace("\r\n.", "\r\n..", $data);
        $data .= "\r\n.\r\n";

        fwrite($socket, $data);

        if (!$this->smtpRead($socket, 250)) {
            fclose($socket);
            return false;
        }

        $this->smtpCommand($socket, 'QUIT', 221);

        fclose($socket);
        return true;
    }

    /**
     * @param resource $socket
     */
    private function smtpCommand($socket, string $command, int $expectedCode): bool
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpRead($socket, $expectedCode);
    }

    /**
     * @param resource $socket
     */
    private function smtpRead($socket, int $expectedCode): bool
    {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        if ($response === '') {
            error_log('Mailer error: empty SMTP response');
            return false;
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            error_log("Mailer error: SMTP expected {$expectedCode}, got {$code}: {$response}");
            return false;
        }

        return true;
    }

    private function buildPlainText(string $html): string
    {
        $text = strip_tags($html);
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
