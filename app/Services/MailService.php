<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;
use RuntimeException;

/**
 * Mail service.
 * 
 * SMTP-backed mailer using PHPMailer.
 */
final class MailService
{
    public function __construct(private readonly array $config) {}

    /**
     * Send an email.
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true, ?string $from = null): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = !empty($this->config['username']);
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $this->config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                $from ?? $this->config['from']['address'],
                $this->config['from']['name']
            );
            $mail->addAddress($to);
            $mail->Subject = $subject;
            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body = $body;
            } else {
                $mail->isHTML(false);
                $mail->Body = $body;
            }
            return $mail->send();
        } catch (MailException $e) {
            throw new RuntimeException('Mail error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Render and send a view-based email.
     */
    public function sendView(string $to, string $subject, string $template, array $data = []): bool
    {
        $body = \App\Core\View::render($template, $data);
        return $this->send($to, $subject, $body, true);
    }
}
