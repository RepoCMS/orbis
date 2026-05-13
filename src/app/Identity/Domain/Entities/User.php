<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Exceptions\UserStatusException;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use DateTimeImmutable;

/**
 * Usuario del sistema.
 * Aggregate root de Identity BC.
 *
 * Transiciones de estado:
 *   UNVERIFIED → ACTIVE (activate)
 *   ACTIVE → INACTIVE (deactivate)
 *   INACTIVE → ACTIVE (reactivate)
 *
 * El bloqueo por intentos fallidos NO es un estado — es temporal via lockedUntil.
 * Un User ACTIVE con lockedUntil en el futuro no puede autenticarse hasta que expire.
 */
final class User
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_DURATION_MINUTES = 15;

    // Campo de auditoría — gestionado por Infrastructure
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private UserIdValueObject $id,
        private UsernameValueObject $username,
        private HashedPasswordValueObject $password,
        private RoleIdValueObject $roleId,
        private UserStatus $status = UserStatus::UNVERIFIED,
        private int $failedLoginAttempts = 0,
        private ?DateTimeImmutable $lockedUntil = null,
    ) {
    }

    public static function register(
        UserIdValueObject $id,
        UsernameValueObject $username,
        HashedPasswordValueObject $password,
        RoleIdValueObject $roleId,
    ): self {
        return new self($id, $username, $password, $roleId);
    }

    public function id(): UserIdValueObject
    {
        return $this->id;
    }

    public function username(): UsernameValueObject
    {
        return $this->username;
    }

    public function password(): HashedPasswordValueObject
    {
        return $this->password;
    }

    public function roleId(): RoleIdValueObject
    {
        return $this->roleId;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function failedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function lockedUntil(): ?DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new DateTimeImmutable();
    }

    /**
     * Activa la cuenta del usuario.
     * Transición: UNVERIFIED → ACTIVE
     * Llamado por el Application Service tras consumir el UserToken de activación.
     */
    public function activate(): void
    {
        if ($this->status !== UserStatus::UNVERIFIED) {
            throw new UserStatusException('Solo se puede activar un usuario con cuenta no verificada.');
        }

        $this->status = UserStatus::ACTIVE;
    }

    /**
     * Desactiva el usuario.
     * Transición: ACTIVE → INACTIVE
     */
    public function deactivate(): void
    {
        if (!$this->isActive()) {
            throw new UserStatusException('Solo se puede desactivar un usuario activo.');
        }

        $this->status = UserStatus::INACTIVE;
    }

    /**
     * Reactiva el usuario.
     * Transición: INACTIVE → ACTIVE
     */
    public function reactivate(): void
    {
        if ($this->status !== UserStatus::INACTIVE) {
            throw new UserStatusException('Solo se puede reactivar un usuario inactivo.');
        }

        $this->status = UserStatus::ACTIVE;
    }

    /**
     * Registra un intento de login fallido.
     * Al alcanzar MAX_FAILED_ATTEMPTS bloquea la cuenta por LOCK_DURATION_MINUTES.
     */
    public function recordFailedLogin(): void
    {
        $this->failedLoginAttempts++;

        if ($this->failedLoginAttempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->lockedUntil = new DateTimeImmutable(
                sprintf('+%d minutes', self::LOCK_DURATION_MINUTES)
            );
        }
    }

    /**
     * Desbloquea la cuenta manualmente (admin o self-service con MFA/token).
     * Resetea el contador de intentos fallidos.
     */
    public function unlock(): void
    {
        $this->lockedUntil = null;
        $this->failedLoginAttempts = 0;
    }

    public function changePassword(HashedPasswordValueObject $newPassword): void
    {
        $this->requireActive();
        $this->password = $newPassword;
        $this->failedLoginAttempts = 0;
    }

    public function changeUsername(UsernameValueObject $newUsername): void
    {
        $this->requireActive();
        $this->username = $newUsername;
    }

    public function assignRole(RoleIdValueObject $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function requireActive(): void
    {
        if (!$this->isActive()) {
            throw new UserStatusException('Esta acción requiere que el usuario esté activo.');
        }
    }
}