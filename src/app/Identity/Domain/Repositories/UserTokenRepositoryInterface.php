<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\UserToken;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;

interface UserTokenRepositoryInterface
{
    public function findById(UserTokenIdValueObject $id): ?UserToken;

    public function findByHash(string $tokenHash): ?UserToken;

    /**
     * Busca el token activo (no expirado) de un usuario por tipo.
     * Solo puede existir uno por userId + type.
     */
    public function findByUserAndType(UserIdValueObject $userId, UserTokenType $type): ?UserToken;

    public function save(UserToken $token): void;

    /**
     * Eliminación física del token (no soft-delete).
     */
    public function delete(UserTokenIdValueObject $id): void;

    /**
     * Elimina todos los tokens de un userId y tipo (reemplazado por uno nuevo).
     */
    public function deleteByUserAndType(UserIdValueObject $userId, UserTokenType $type): void;

    /**
     * Elimina todos los tokens expirados (job de limpieza).
     */
    public function deleteExpired(): void;
}
