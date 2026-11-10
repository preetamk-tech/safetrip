<?php

if (is_file(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function write_mail_log(string $toEmail, string $subject, string $body): bool
{
    $path = APP_ROOT . '/logs/mail.log';
    $directory = dirname($path);

    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    $entry = sprintf(
        "[%s] to=%s subject=%s body=%s%s",
        date('Y-m-d H:i:s'),
        $toEmail,
        str_replace(PHP_EOL, ' ', $subject),
        str_replace([PHP_EOL, "\r"], ' ', strip_tags($body)),
        PHP_EOL
    );

    return @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX) !== false;
}

function log_mail_error(string $message, array $context = []): void
{
    $path = APP_ROOT . '/logs/php-error.log';
    $directory = dirname($path);

    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    $entry = sprintf(
        "[%s] %s %s%s",
        date('Y-m-d H:i:s'),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
        PHP_EOL
    );

    @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
}

function sendEmail(string $to, string $subject, string $message): bool
{
    $mailMode = (string) env('MAIL_MODE', 'smtp');

    if ($mailMode === 'log') {
        return write_mail_log($to, $subject, $message);
    }

    if (!class_exists(PHPMailer::class)) {
        log_mail_error('PHPMailer not installed', ['email' => $to]);
        return write_mail_log($to, $subject, $message);
    }

    $host = (string) env('MAIL_HOST', 'smtp.gmail.com');
    $port = (int) env('MAIL_PORT', 587);
    $username = (string) env('MAIL_USERNAME', '');
    $rawPassword = (string) env('MAIL_PASSWORD', '');
    $password = preg_replace('/[\s-]+/', '', $rawPassword) ?? $rawPassword;
    $encryption = (string) env('MAIL_ENCRYPTION', 'tls');
    $fromAddress = (string) env('MAIL_FROM_ADDRESS', $username);
    $fromName = (string) env('MAIL_FROM_NAME', APP_NAME);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ];

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags($message)));
        $mail->send();

        return true;
    } catch (Exception $exception) {
        log_mail_error('Mail send failure', [
            'email' => $to,
            'message' => $exception->getMessage(),
            'mailer_username' => $username,
            'from_address' => $fromAddress,
        ]);

        return write_mail_log($to, $subject, $message);
    }
}
