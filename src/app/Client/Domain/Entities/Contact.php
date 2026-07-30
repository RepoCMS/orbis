<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\ContactStatus;
use App\Client\Domain\Exceptions\ContactNameException;
use App\Client\Domain\Exceptions\ContactStatusException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\StringNormalizer;
use DateTimeImmutable;

/**
 * Contacto de un cliente.
 * Representa a una persona física que es punto de contacto para un Client.
 * Puede tener opcionalmente un User asociado para acceso al sistema.
 *
 * Un Contact sin userId puede seguir recibiendo notificaciones del sistema
 * (ej: estado de tickets de su banco) sin tener acceso de login.
 */
final class Contact
{
    // Campo de auditoría — gestionado por Infrastructure
    private ?DateTimeImmutable $deletedAt = null;

    private const MIN_NAME_LENGTH = 2;
    private const MAX_NAME_LENGTH = 100;

    private function __construct(
        private ContactIdValueObject $id,
        private ClientIdValueObject $clientId,
        private ?UserIdValueObject $userId,
        private string $firstName,
        private string $lastName,
        private ContactEmailValueObject $email,
        private ContactPhoneNumberValueObject $phoneNumber,
        private ContactRoleValueObject $role,
        private ContactStatus $status = ContactStatus::ACTIVE,
    ) {
        $this->firstName = StringNormalizer::normalizeName($this->firstName);
        $this->lastName  = StringNormalizer::normalizeName($this->lastName);

        $this->assertValidName($this->firstName, 'nombre');
        $this->assertValidName($this->lastName, 'apellido');
    }

    public static function create(
        ContactIdValueObject $id,
        ClientIdValueObject $clientId,
        string $firstName,
        string $lastName,
        ContactEmailValueObject $email,
        ContactPhoneNumberValueObject $phoneNumber,
        ContactRoleValueObject $role,
        ?UserIdValueObject $userId = null,
    ): self {
        return new self($id, $clientId, $userId, $firstName, $lastName, $email, $phoneNumber, $role);
    }

    public function id(): ContactIdValueObject
    {
        return $this->id;
    }

    public function clientId(): ClientIdValueObject
    {
        return $this->clientId;
    }

    public function userId(): ?UserIdValueObject
    {
        return $this->userId;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function email(): ContactEmailValueObject
    {
        return $this->email;
    }

    public function phoneNumber(): ContactPhoneNumberValueObject
    {
        return $this->phoneNumber;
    }

    public function role(): ContactRoleValueObject
    {
        return $this->role;
    }

    public function status(): ContactStatus
    {
        return $this->status;
    }

    public function linkToUser(string $userId): void
    {
        $this->userId = new UserIdValueObject($userId);
    }

    public function hasUser(): bool
    {
        return $this->userId !== null;
    }

    public function isActive(): bool
    {
        return $this->status === ContactStatus::ACTIVE;
    }

    public function changeName(string $firstName, string $lastName): void
    {
        $normalizedFirst = StringNormalizer::normalizeName($firstName);
        $normalizedLast  = StringNormalizer::normalizeName($lastName);

        $this->assertValidName($normalizedFirst, 'nombre');
        $this->assertValidName($normalizedLast, 'apellido');

        $this->firstName = $normalizedFirst;
        $this->lastName  = $normalizedLast;
    }

    public function changeEmail(ContactEmailValueObject $email): void
    {
        $this->email = $email;
    }

    public function changePhoneNumber(ContactPhoneNumberValueObject $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function changeRole(ContactRoleValueObject $role): void
    {
        $this->role = $role;
    }

    /**
     * Vincula un User al contacto para acceso al portal.
     * Guard: userId debe ser null actualmente.
     */
    public function linkUser(UserIdValueObject $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Desvincula el User del contacto.
     * El User permanece ACTIVE en Identity BC — el contacto puede
     * seguir recibiendo notificaciones sin acceso al login.
     * Efecto sobre el User en Identity BC: responsabilidad del Application Service.
     */
    public function unlinkUser(): void
    {
        $this->userId = null;
    }

    /**
     * Desactiva el contacto.
     * Guard: no puede ser el último contacto activo del cliente (responsabilidad del Application Service).
     * Efecto sobre User en Identity BC: responsabilidad del Application Service.
     */
    public function deactivate(): void
    {
        if (!$this->isActive()) {
            throw new ContactStatusException('El contacto ya se encuentra inactivo.');
        }

        $this->status = ContactStatus::INACTIVE;
    }

    public function reactivate(): void
    {
        if ($this->isActive()) {
            throw new ContactStatusException('El contacto ya se encuentra activo.');
        }

        $this->status = ContactStatus::ACTIVE;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function assertValidName(string $normalizedValue, string $field): void
    {
        if ($normalizedValue === '') {
            throw new ContactNameException("El {$field} del contacto no puede estar vacío.");
        }

        $length = mb_strlen($normalizedValue);

        if ($length < self::MIN_NAME_LENGTH) {
            throw new ContactNameException(
                sprintf('El %s del contacto debe tener al menos %d caracteres.', $field, self::MIN_NAME_LENGTH)
            );
        }

        if ($length > self::MAX_NAME_LENGTH) {
            throw new ContactNameException(
                sprintf('El %s del contacto no puede exceder los %d caracteres.', $field, self::MAX_NAME_LENGTH)
            );
        }
    }
}

