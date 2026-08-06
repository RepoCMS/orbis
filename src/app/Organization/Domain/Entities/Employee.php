<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Exceptions\EmployeeAlreadyHasUserException;
use App\Organization\Domain\Exceptions\EmployeeNameException;
use App\Organization\Domain\Exceptions\EmployeePhoneNumberException;
use App\Organization\Domain\Exceptions\EmployeeProfileException;
use App\Organization\Domain\Exceptions\EmployeeStatusException;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use App\Shared\Domain\StringNormalizer;
use DateTimeImmutable;

final class Employee implements TimestampableInterface
{
    // Campos de auditoría — gestionados por TimestampsSubscriber (Infrastructure)
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    private const MIN_NAME_LENGTH = 5;
    private const MAX_NAME_LENGTH = 100;

    private function __construct(
        private EmployeeIdValueObject $id,
        private ?UserIdValueObject $userId,
        private EmployeeIdentificationNumberValueObject $identificationNumber,
        private string $firstName,
        private string $lastName,
        private EmployeeEmailValueObject $email,
        private string $phoneNumber,
        private EmployeeStatus $status = EmployeeStatus::ACTIVE,
        private DateTimeImmutable $hiredAt,
        private ?DateTimeImmutable $terminatedAt,
        private EmployeeProfile $profile = EmployeeProfile::ADMINISTRATIVE,
        private ?ServiceZoneIdValueObject $serviceZoneId = null,
    ) {
        $this->firstName = StringNormalizer::normalizeName($this->firstName);
        $this->lastName = StringNormalizer::normalizeName($this->lastName);
        $this->phoneNumber = StringNormalizer::normalizePhoneNumber($this->phoneNumber);

        $this->assertValidName($this->firstName, 'nombre');
        $this->assertValidName($this->lastName, 'apellido');
        $this->assertPhoneNumber($this->phoneNumber);

        if ($this->status->isEmployed() && $this->terminatedAt !== null) {
            throw new EmployeeStatusException('Un empleado contratado no puede tener fecha de terminación.');
        }

        if ($this->status === EmployeeStatus::INACTIVE && $this->terminatedAt === null) {
            throw new EmployeeStatusException('Un empleado inactivo debe tener fecha de terminación.');
        }

        if ($this->terminatedAt !== null && $this->terminatedAt < $this->hiredAt) {
            throw new EmployeeStatusException('La fecha de terminación no puede ser anterior a la fecha de contratación.');
        }
    }

    public static function hire(
        EmployeeIdValueObject $id,
        EmployeeIdentificationNumberValueObject $identificationNumber,
        string $firstName,
        string $lastName,
        EmployeeEmailValueObject $email,
        string $phoneNumber,
        DateTimeImmutable $hiredAt,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
        ?UserIdValueObject $userId = null,
        EmployeeProfile $profile = EmployeeProfile::ADMINISTRATIVE,
    ): self {
        return new self($id, $userId, $identificationNumber, $firstName, $lastName, $email, $phoneNumber, $status, $hiredAt, null, $profile);
    }

    public function linkUser(UserIdValueObject $userId): void
    {
        if ($this->userId !== null) {
            throw EmployeeAlreadyHasUserException::withId((string) $this->id);
        }

        $this->userId = $userId;
    }

    public function canLinkUser(UserIdValueObject $userId): bool
    {
        return $this->userId === null || (string) $this->userId === (string) $userId;
    }

    public function unlinkUser(): void
    {
        $this->userId = null;
    }

    public function profile(): EmployeeProfile
    {
        return $this->profile;
    }

    public function isTechnician(): bool
    {
        return $this->profile->isTechnician();
    }

    public function serviceZoneId(): ?ServiceZoneIdValueObject
    {
        return $this->serviceZoneId;
    }

    /**
     * Solo empleados con perfil TECHNICIAN pueden ser asignados a una zona de servicio.
     */
    public function assignToZone(ServiceZoneIdValueObject $zoneId): void
    {
        if (!$this->profile->isTechnician()) {
            throw new EmployeeProfileException('Solo los técnicos pueden ser asignados a una zona de servicio.');
        }

        $this->serviceZoneId = $zoneId;
    }

    public function removeFromZone(): void
    {
        $this->serviceZoneId = null;
    }

    public function id(): EmployeeIdValueObject
    {
        return $this->id;
    }

    public function userId(): ?UserIdValueObject
    {
        return $this->userId;
    }

    public function hasUser(): bool
    {
        return $this->userId !== null;
    }

    public function identificationNumber(): EmployeeIdentificationNumberValueObject
    {
        return $this->identificationNumber;
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

    public function email(): EmployeeEmailValueObject
    {
        return $this->email;
    }

    public function phoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function hiredAt(): DateTimeImmutable
    {
        return $this->hiredAt;
    }

    public function terminatedAt(): ?DateTimeImmutable
    {
        return $this->terminatedAt;
    }

    public function status(): EmployeeStatus
    {
        return $this->status;
    }

    public function changeFirstName(string $newFirstName): void
    {
        $normalized = StringNormalizer::normalizeName($newFirstName);
        $this->assertValidName($normalized, 'nombre');
        $this->firstName = $normalized;
    }

    public function changeLastName(string $newLastName): void
    {
        $normalized = StringNormalizer::normalizeName($newLastName);
        $this->assertValidName($normalized, 'apellido');
        $this->lastName = $normalized;
    }

    public function changePhoneNumber(string $newPhoneNumber): void
    {
        $normalized = StringNormalizer::normalizePhoneNumber($newPhoneNumber);
        $this->assertPhoneNumber($normalized);
        $this->phoneNumber = $normalized;
    }

    public function changeEmail(EmployeeEmailValueObject $newEmail): void
    {
        $this->email = $newEmail;
    }

    public function changeProfile(EmployeeProfile $profile): void
    {
        if ($this->profile === $profile) {
            return;
        }

        // Si deja de ser técnico, se desvincula de su zona
        if ($this->profile->isTechnician() && !$profile->isTechnician()) {
            $this->serviceZoneId = null;
        }

        $this->profile = $profile;
    }

    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::ACTIVE;
    }

    public function isAvailableForAssignment(): bool
    {
        return $this->status->isAvailableForAssignment();
    }

    public function terminate(): void
    {
        if ($this->status === EmployeeStatus::INACTIVE) {
            throw new EmployeeStatusException('El empleado ya se encuentra inactivo.');
        }

        $this->status = EmployeeStatus::INACTIVE;
        $this->terminatedAt = new DateTimeImmutable();

        if ($this->terminatedAt < $this->hiredAt) {
            throw new EmployeeStatusException('La fecha de terminación no puede ser anterior a la fecha de contratación.');
        }
    }

    public function reactivate(): void
    {
        if ($this->status !== EmployeeStatus::INACTIVE) {
            throw new EmployeeStatusException('Solo se puede reactivar un empleado inactivo.');
        }

        $this->status = EmployeeStatus::ACTIVE;
        $this->terminatedAt = null;
    }

    public function startVacation(): void
    {
        if ($this->status !== EmployeeStatus::ACTIVE) {
            throw new EmployeeStatusException('Solo se puede poner en vacaciones a un empleado activo.');
        }

        $this->status = EmployeeStatus::ON_VACATION;
    }

    public function returnFromVacation(): void
    {
        if ($this->status !== EmployeeStatus::ON_VACATION) {
            throw new EmployeeStatusException('El empleado no se encuentra en vacaciones.');
        }

        $this->status = EmployeeStatus::ACTIVE;
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

    private function assertPhoneNumber(string $phoneNumber): void
    {
        if ($phoneNumber === '') {
            throw new EmployeePhoneNumberException('El número de teléfono del empleado no puede estar vacío.');
        }

        if (!preg_match('/^09\d{8}$/', $phoneNumber)) {
            throw new EmployeePhoneNumberException('El número de teléfono debe iniciar con 09 y tener exactamente 10 dígitos.');
        }
    }

    private function assertValidName(string $normalizedValue, string $field): void
    {
        if ($normalizedValue === '') {
            throw new EmployeeNameException("El {$field} no puede estar vacío.");
        }

        $length = mb_strlen($normalizedValue);

        if ($length < self::MIN_NAME_LENGTH) {
            throw new EmployeeNameException("El {$field} no alcanza la longitud mínima requerida.");
        }

        if ($length > self::MAX_NAME_LENGTH) {
            throw new EmployeeNameException("El {$field} excede la longitud máxima permitida.");
        }
    }
}
