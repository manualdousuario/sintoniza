<?php

declare(strict_types=1);

namespace Sintoniza\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use Sintoniza\Library\Language;

class MailService
{
    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink, string $language = 'en'): bool
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

            $subjectTpl = Language::translate($language, 'emails.password_reset_subject');
            $bodyTpl    = Language::translate($language, 'emails.password_reset_body');

            $mail->Subject = sprintf($subjectTpl, TITLE);
            $mail->Body    = sprintf($bodyTpl, $toName, $resetLink);

            $mail->send();

            return true;
        } catch (MailException) {
            return false;
        }
    }
}
