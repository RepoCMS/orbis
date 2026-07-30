<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Identity\Domain\Service\ForgotPasswordEmailSenderInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementación temporal del envío de email de restablecimiento de contraseña.
 *
 * En lugar de enviar un email real, registra el token en los logs para poder
 * usarlo durante desarrollo/pruebas. En producción, reemplazar por una
 * implementación que use Symfony Mailer.
 */
final readonly class LoggerForgotPasswordEmailSender implements ForgotPasswordEmailSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function sendPasswordResetEmail(string $toEmail, string $username, string $token): void
    {
        $this->logger->info(
            sprintf(
                '[FORGOT PASSWORD] Enviar email a: %s | Usuario: %s | Token: %s',
                $toEmail,
                $username,
                $token,
            ),
        );
    }
}
