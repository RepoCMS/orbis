<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Exceptions\EmployeeNameException;
use App\Organization\Domain\Exceptions\EmployeeStatusException;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    private const VALID_ID = '1710034065';

    private function createEmployee(): Employee
    {
        return Employee::hire(
            new EmployeeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdentificationNumberValueObject(self::VALID_ID),
            'Juan Carlos Ernesto',
            'Pérez López Martinez',
            new EmployeeEmailValueObject('juan@empresa.com'),
            '0991234567',
            new DateTimeImmutable('2024-01-15'),
        );
    }

    // --- Creación (hire) ---

    public function testHireCreatesActiveEmployee(): void
    {
        $employee = $this->createEmployee();

        self::assertSame(EmployeeStatus::ACTIVE, $employee->status());
        self::assertTrue($employee->isActive());
    }

    public function testHireStoresAllFields(): void
    {
        $employee = $this->createEmployee();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $employee->id());
        self::assertSame(self::VALID_ID, (string) $employee->identificationNumber());
        self::assertSame('Juan Carlos Ernesto', $employee->firstName());
        self::assertSame('Pérez López Martinez', $employee->lastName());
        self::assertSame('Juan Carlos Ernesto Pérez López Martinez', $employee->fullName());
        self::assertSame('juan@empresa.com', (string) $employee->email());
        self::assertSame('0991234567', $employee->phoneNumber());
        self::assertEquals(new DateTimeImmutable('2024-01-15'), $employee->hiredAt());
        self::assertNull($employee->terminatedAt());
    }

    public function testHireNormalizesNames(): void
    {
        $employee = Employee::hire(
            new EmployeeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdentificationNumberValueObject(self::VALID_ID),
            '  Juan   Carlos Ernesto  ',
            '  Pérez   López Martinez  ',
            new EmployeeEmailValueObject('juan@empresa.com'),
            '0991234567',
            new DateTimeImmutable('2024-01-15'),
        );

        self::assertSame('Juan Carlos Ernesto', $employee->firstName());
        self::assertSame('Pérez López Martinez', $employee->lastName());
    }

    // --- Validación de nombre ---

    public function testHireThrowsWhenFirstNameTooShort(): void
    {
        $this->expectException(EmployeeNameException::class);

        Employee::hire(
            new EmployeeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdentificationNumberValueObject(self::VALID_ID),
            'Juan',
            'Pérez López Martinez',
            new EmployeeEmailValueObject('juan@empresa.com'),
            '0991234567',
            new DateTimeImmutable('2024-01-15'),
        );
    }

    public function testHireThrowsWhenLastNameEmpty(): void
    {
        $this->expectException(EmployeeNameException::class);

        Employee::hire(
            new EmployeeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new EmployeeIdentificationNumberValueObject(self::VALID_ID),
            'Juan Carlos Ernesto',
            '',
            new EmployeeEmailValueObject('juan@empresa.com'),
            '0991234567',
            new DateTimeImmutable('2024-01-15'),
        );
    }

    // --- Change First Name ---

    public function testChangeFirstNameUpdatesValue(): void
    {
        $employee = $this->createEmployee();

        $employee->changeFirstName('María Fernanda Gabriela');

        self::assertSame('María Fernanda Gabriela', $employee->firstName());
    }

    public function testChangeFirstNameThrowsWhenTooShort(): void
    {
        $employee = $this->createEmployee();

        $this->expectException(EmployeeNameException::class);
        $employee->changeFirstName('Ana');
    }

    // --- Change Last Name ---

    public function testChangeLastNameUpdatesValue(): void
    {
        $employee = $this->createEmployee();

        $employee->changeLastName('González Paredes Mendoza');

        self::assertSame('González Paredes Mendoza', $employee->lastName());
    }

    public function testChangeLastNameThrowsWhenTooShort(): void
    {
        $employee = $this->createEmployee();

        $this->expectException(EmployeeNameException::class);
        $employee->changeLastName('Gom');
    }

    // --- Change Phone ---

    public function testChangePhoneNumberUpdatesValue(): void
    {
        $employee = $this->createEmployee();

        $employee->changePhoneNumber('0997654321');

        self::assertSame('0997654321', $employee->phoneNumber());
    }

    // --- Terminate ---

    public function testTerminateSetsInactiveStatus(): void
    {
        $employee = $this->createEmployee();

        $employee->terminate();

        self::assertSame(EmployeeStatus::INACTIVE, $employee->status());
        self::assertFalse($employee->isActive());
        self::assertNotNull($employee->terminatedAt());
    }

    public function testTerminateThrowsWhenAlreadyInactive(): void
    {
        $employee = $this->createEmployee();
        $employee->terminate();

        $this->expectException(EmployeeStatusException::class);
        $employee->terminate();
    }

    // --- Reactivate ---

    public function testReactivateRestoresActiveStatus(): void
    {
        $employee = $this->createEmployee();
        $employee->linkUser(new UserIdValueObject('01JCPX8XYZ0000000000000001'));
        $employee->terminate();

        $employee->reactivate();

        self::assertSame(EmployeeStatus::ACTIVE, $employee->status());
        self::assertTrue($employee->isActive());
        self::assertNull($employee->terminatedAt());
    }

    public function testReactivateThrowsWhenAlreadyActive(): void
    {
        $employee = $this->createEmployee();

        $this->expectException(EmployeeStatusException::class);
        $employee->reactivate();
    }

    public function testTerminateDoesNotValidateUserId(): void
    {
        $employee = $this->createEmployee();

        // Employee without userId — terminate should work without validation
        $employee->terminate();

        self::assertSame(EmployeeStatus::INACTIVE, $employee->status());
        self::assertFalse($employee->isActive());
        self::assertNotNull($employee->terminatedAt());
    }

    // --- Full Lifecycle ---

    public function testFullLifecycle(): void
    {
        $employee = $this->createEmployee();
        $employee->linkUser(new UserIdValueObject('01JCPX8XYZ0000000000000001'));

        self::assertTrue($employee->isActive());

        $employee->changeFirstName('María Elena Josefina');
        self::assertSame('María Elena Josefina', $employee->firstName());

        $employee->terminate();
        self::assertFalse($employee->isActive());
        self::assertNotNull($employee->terminatedAt());

        $employee->reactivate();
        self::assertTrue($employee->isActive());
        self::assertNull($employee->terminatedAt());
    }
}
