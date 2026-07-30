<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;

/**
 * Código de respaldo MFA (One-Time Use).
 *
 * Cada código permite al usuario acceder sin TOTP cuando no tiene
 * acceso a su dispositivo móvil. Los códigos se usan una sola vez
 * y se marcan como usados (no se eliminan).
 *
 * Ciclo de vida:
 *   generate() → código activo (usedAt = null)
 *   markAsUsed() → código consumido (usedAt = now)
 */
final class UserMfaBackupCode
{
    private function __construct(
        private UserMfaBackupCodeIdValueObject $id,
        private UserIdValueObject $userId,
        private string $code,
        private ?DateTimeImmutable $usedAt,
        private DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * Genera un nuevo código de respaldo.
     */
    public static function generate(
        UserMfaBackupCodeIdValueObject $id,
        UserIdValueObject $userId,
        string $code,
    ): self {
        return new self(
            $id,
            $userId,
            $code,
            null,
            new DateTimeImmutable(),
        );
    }

    /**
     * Marca el código como usado.
     */
    public function markAsUsed(): void
    {
        $this->usedAt = new DateTimeImmutable();
    }

    public function id(): UserMfaBackupCodeIdValueObject
    {
        return $this->id;
    }

    public function userId(): UserIdValueObject
    {
        return $this->userId;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function usedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
