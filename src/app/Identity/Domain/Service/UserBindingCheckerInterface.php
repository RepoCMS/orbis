<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\Exceptions\UserAlreadyBoundException;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

/**
 * Puerto de dominio que garantiza la invariante:
 * "Un User sólo puede estar vinculado a UNA persona (Employee o Contact)."
 *
 * La implementación concreta vive en Infrastructure y consulta
 * ambos repositorios (ContactRepositoryInterface + EmployeeRepositoryInterface).
 */
interface UserBindingCheckerInterface
{
    /**
     * @throws UserAlreadyBoundException si el usuario ya está asignado a otra entidad.
     */
    public function ensureNotBound(UserIdValueObject $userId): void;
}
