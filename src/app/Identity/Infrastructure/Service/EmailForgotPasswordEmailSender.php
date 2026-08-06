<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Identity\Domain\Service\ForgotPasswordEmailSenderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class EmailForgotPasswordEmailSender implements ForgotPasswordEmailSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
    ) {
    }

    public function sendPasswordResetEmail(string $toEmail, string $username, string $token): void
    {
        $resetUrl = sprintf('%s/reset-password?token=%s', $this->getAppBaseUrl(), $token);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Recuperación de contraseña - Orbis')
            ->html($this->buildHtmlBody($username, $resetUrl));

        $this->mailer->send($email);
    }

    private function buildHtmlBody(string $username, string $resetUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Recuperación de contraseña</h2>
        <p>Hola <strong>{$username}</strong>,</p>
        <p>Has solicitado restablecer tu contraseña en Orbis. Haz clic en el siguiente enlace para continuar:</p>
        <p style="text-align: center;">
            <a href="{$resetUrl}" 
               style="display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #fff; 
                      text-decoration: none; border-radius: 6px; font-weight: bold;">
                Restablecer contraseña
            </a>
        </p>
        <p>Este enlace expirará en 1 hora por seguridad.</p>
        <p>Si no solicitaste este cambio, ignora este mensaje.</p>
        <hr>
        <p style="color: #6b7280; font-size: 0.875rem;">Orbis - Automated Cash Management Solutions S.A.</p>
    </div>
</body>
</html>
HTML;
    }

    private function getAppBaseUrl(): string
    {
        $url = getenv('APP_BASE_URL');

        return $url ? rtrim($url, '/') : 'https://orbis.app';
    }
}
