<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\ValueObjects\UserIdValueObject;

/**
 * Puerto de dominio para resolver el email de un usuario a partir de su ID.
 *
 * Un User no tiene email propio (REQ-ID-001). El email puede venir de un
 * Employee (Organization BC) o de un Contact (Client BC) vinculado al userId.
 *
 * La implementación vive en Infrastructure y hace la consulta cross-BC.
 */
interface UserEmailResolverInterface
{
    /**
     * Devuelve el email asociado al userId, o null si no se encuentra.
     * Busca primero en Employee, luego en Contact.
     */
    public function resolveEmail(UserIdValueObject $userId): ?string;
}
