<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\ValueObjects\UserIdValueObject;

/**
 * Puerto de dominio para resolver un userId a partir de su email.
 *
 * Un User no tiene email propio (REQ-ID-001). El email puede pertenecer a un
 * Employee (Organization BC) o a un Contact (Client BC) vinculado al userId.
 *
 * La implementación vive en Infrastructure y hace la consulta cross-BC.
 */
interface UserByEmailResolverInterface
{
    /**
     * Devuelve el userId asociado al email, o null si no se encuentra.
     * Busca primero en Employee, luego en Contact.
     */
    public function resolveByEmail(string $email): ?UserIdValueObject;
}
