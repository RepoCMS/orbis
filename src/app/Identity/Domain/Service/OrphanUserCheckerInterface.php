<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\Exceptions\UserOrphanException;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

interface OrphanUserCheckerInterface
{
    /**
     * Verifica que el usuario esté vinculado a un Employee o Contact.
     *
     * @throws UserOrphanException si el usuario no está vinculado
     */
    public function ensureIsBound(UserIdValueObject $userId): void;

    /**
     * Retorna true si el usuario está vinculado a un Employee o Contact.
     */
    public function isBound(UserIdValueObject $userId): bool;

    /**
     * Retorna todos los User IDs huérfanos (excluye soft-deleted).
     *
     * @return string[]
     */
    public function findAllOrphanUserIds(): array;
}
