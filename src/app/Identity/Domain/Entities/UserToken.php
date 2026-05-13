<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\ValueObjects\PlainTokenValueObject;
use App\Identity\Domain\ValueObjects\TokenHashValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use DateTimeImmutable;

/**
 * Entidad que gestiona tokens temporales del ciclo de vida de la cuenta.
 * Los tokens consumidos o expirados se eliminan físicamente — no se soft-delete.
 *
 * Expiración por tipo:
 *   ACTIVATION     → 48 horas
 *   PASSWORD_RESET → 1 hora
 *   ACCOUNT_UNLOCK → 1 hora
 */
final class UserToken
{
    private const EXPIRATION_HOURS = [
        UserTokenType::ACTIVATION->value     => 48,
        UserTokenType::PASSWORD_RESET->value => 1,
        UserTokenType::ACCOUNT_UNLOCK->value => 1,
    ];

    private function __construct(
        private UserTokenIdValueObject $id,
        private UserIdValueObject $userId,
        private UserTokenType $type,
        private TokenHashValueObject $tokenHash,
        private DateTimeImmutable $expiresAt,
    ) {
    }

    /**
     * Genera un nuevo token. Retorna tanto la entidad como el PlainToken (solo para envío por email).
     *
     * @return array{entity: self, plainToken: PlainTokenValueObject}
     */
    public static function generate(
        UserTokenIdValueObject $id,
        UserIdValueObject $userId,
        UserTokenType $type,
    ): array {
        $plainToken = PlainTokenValueObject::generate();
        $tokenHash  = TokenHashValueObject::fromPlainToken($plainToken);
        $hours      = self::EXPIRATION_HOURS[$type->value];
        $expiresAt  = new DateTimeImmutable("+{$hours} hours");

        $entity = new self($id, $userId, $type, $tokenHash, $expiresAt);

        return ['entity' => $entity, 'plainToken' => $plainToken];
    }

    public function id(): UserTokenIdValueObject
    {
        return $this->id;
    }

    public function userId(): UserIdValueObject
    {
        return $this->userId;
    }

    public function type(): UserTokenType
    {
        return $this->type;
    }

    public function tokenHash(): TokenHashValueObject
    {
        return $this->tokenHash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    /**
     * Valida el token sin consumirlo. Retorna true si el hash coincide y no está expirado.
     */
    public function isValid(PlainTokenValueObject $plainToken): bool
    {
        return !$this->isExpired() && $this->tokenHash->matches($plainToken);
    }

    /**
     * Verifica el token para consumirlo. Lanza excepción si es inválido o expirado.
     * Después de llamar a este método, el Application Service debe eliminar físicamente el token.
     */
    public function consume(PlainTokenValueObject $plainToken): void
    {
        if ($this->isExpired()) {
            throw new TokenException('El token ha expirado.');
        }

        if (!$this->tokenHash->matches($plainToken)) {
            throw new TokenException('El token no es válido.');
        }
    }
}
