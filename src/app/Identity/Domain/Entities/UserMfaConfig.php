<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use DateTimeImmutable;

/**
 * Configuración TOTP del usuario.
 * Entidad separada del Aggregate User — un User puede o no tener MFA configurado.
 *
 * Ciclo de vida:
 *   setup()   → crea pendiente de confirmar (isEnabled=false)
 *   confirm() → activa el MFA (isEnabled=true, confirmedAt=now)
 *   disable() → desactiva el MFA (isEnabled=false, confirmedAt=null)
 */
final class UserMfaConfig
{
    private ?DateTimeImmutable $createdAt = null;

    private function __construct(
        private UserMfaConfigIdValueObject $id,
        private UserIdValueObject $userId,
        private string $secret,
        private bool $isEnabled,
        private ?DateTimeImmutable $confirmedAt,
    ) {
    }

    /**
     * Crea una nueva configuración MFA en estado pendiente de confirmación.
     * El caller debe persistir la entidad y luego llamar confirm() tras verificar el código.
     */
    public static function setup(
        UserMfaConfigIdValueObject $id,
        UserIdValueObject $userId,
        string $secret,
    ): self {
        $config = new self($id, $userId, $secret, false, null);
        $config->createdAt = new DateTimeImmutable();

        return $config;
    }

    /**
     * Confirma el MFA después de que el usuario ha verificado un código TOTP válido.
     * Transición: pendiente → habilitado
     */
    public function confirm(): void
    {
        if ($this->isEnabled) {
            throw new \DomainException('El MFA ya está habilitado para este usuario.');
        }

        $this->isEnabled   = true;
        $this->confirmedAt = new DateTimeImmutable();
    }

    /**
     * Actualiza el secret (re-setup) manteniendo el estado actual.
     * El caller debe llamar confirm() tras verificar el nuevo código.
     */
    public function updateSecret(string $newSecret): void
    {
        $this->secret      = $newSecret;
        $this->isEnabled   = false;
        $this->confirmedAt = null;
    }

    /**
     * Deshabilita el MFA. El secret se conserva pero isEnabled=false.
     */
    public function disable(): void
    {
        $this->isEnabled   = false;
        $this->confirmedAt = null;
    }

    public function id(): UserMfaConfigIdValueObject
    {
        return $this->id;
    }

    public function userId(): UserIdValueObject
    {
        return $this->userId;
    }

    public function secret(): string
    {
        return $this->secret;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
