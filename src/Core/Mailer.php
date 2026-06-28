<?php

declare(strict_types=1);

namespace eFiction\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Mailer wrapper around PHPMailer 6.x.
 */
class Mailer
{
    public function __construct(private readonly Config $config) {}

    public function send(string $to, string $subject, string $body, string $toName = ''): bool
    {
        $mail = new PHPMailer(true);

        try {
            $method = $this->config->get('mail.method', 'mail');
            if ($method === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = $this->config->get('mail.smtp.host', '');
                $mail->Port       = (int) $this->config->get('mail.smtp.port', 587);
                $mail->SMTPAuth   = (bool) $this->config->get('mail.smtp.auth', false);
                $mail->Username   = $this->config->get('mail.smtp.username', '');
                $mail->Password   = $this->config->get('mail.smtp.password', '');
                $mail->SMTPSecure = match ($this->config->get('mail.smtp.secure', 'tls')) {
                    'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                    'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                    default => '',
                };
            } elseif ($method === 'sendmail') {
                $mail->isSendmail();
            } else {
                $mail->isMail();
            }

            $mail->setFrom(
                $this->config->get('mail.from', 'noreply@example.com'),
                $this->config->get('site.title', 'eFiction')
            );
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            $mail->CharSet = 'UTF-8';

            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}
