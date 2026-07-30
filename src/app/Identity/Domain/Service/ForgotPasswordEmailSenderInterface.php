<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

/**
 * Puerto de dominio para envío del correo de restablecimiento de contraseña.
 *
 * Este contrato desacopla el Application Handler del mecanismo de envío.
 * La implementación concreta vive en Infrastructure (p. ej. Logger, Symfony Mailer).
 */
interface ForgotPasswordEmailSenderInterface
{
    /**
     * Envía al usuario un correo con el enlace para restablecer su contraseña.
     *
     * @param string $toEmail  Dirección de correo del usuario
     * @param string $username Nombre de usuario para mostrar en el email
     * @param string $token    Token en texto plano (se embebe en el link de reseteo)
     */
    public function sendPasswordResetEmail(string $toEmail, string $username, string $token): void;
}
