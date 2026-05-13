<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

/**
 * Puerto de dominio para envío de notificaciones de cuenta.
 * La implementación concreta vive en Infrastructure (Symfony Mailer).
 */
interface AccountNotifierInterface
{
    /**
     * Envía el correo de activación de cuenta al usuario.
     *
     * @param string $toEmail  Dirección de destino
     * @param string $username Nombre de usuario para mostrar en el email
     * @param string $token    Token en texto plano (se embebe en el link)
     */
    public function sendActivationEmail(string $toEmail, string $username, string $token): void;
}
