<?php

declare(strict_types=1);

namespace Sintoniza\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class MailService
{
    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): bool
    {
        if (empty(SMTP_HOST)) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = (int) SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_NAME ?: TITLE);
            $mail->addAddress($toEmail, $toName);

            $mail->Subject = 'Recuperação de senha - ' . TITLE;
            $mail->Body    = sprintf(
                "Olá %s,\n\nClique no link abaixo para redefinir sua senha:\n%s\n\nEste link expira em 1 hora.",
                $toName,
                $resetLink
            );

            $mail->send();

            return true;
        } catch (MailException) {
            return false;
        }
    }
}
