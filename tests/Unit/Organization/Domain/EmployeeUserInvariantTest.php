<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Exceptions\EmployeeAlreadyHasUserException;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeUserInvariantTest extends TestCase
{
    private const EMPLOYEE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const USER_ID_A   = '01JCPX8XYZ0000000000000001';
    private const USER_ID_B   = '01JCPX8XYZ0000000000000002';

    private function createEmployee(): Employee
    {
        return Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
            firstName: 'Juan Carlos',
            lastName: 'Perez Ruiz',
            email: new EmployeeEmailValueObject('jperez@example.com'),
            phoneNumber: '0999999999',
            hiredAt: new DateTimeImmutable('2024-01-01'),
        );
    }

    public function testLinkUserSetsUserId(): void
    {
        $employee = $this->createEmployee();
        $userId = new UserIdValueObject(self::USER_ID_A);

        $employee->linkUser($userId);

        self::assertSame(self::USER_ID_A, (string) $employee->userId());
        self::assertTrue($employee->hasUser());
    }

    public function testLinkUserThrowsWhenAlreadyLinked(): void
    {
        $employee = $this->createEmployee();
        $employee->linkUser(new UserIdValueObject(self::USER_ID_A));

        $this->expectException(EmployeeAlreadyHasUserException::class);

        $employee->linkUser(new UserIdValueObject(self::USER_ID_B));
    }

    public function testUnlinkUserClearsUserId(): void
    {
        $employee = $this->createEmployee();
        $employee->linkUser(new UserIdValueObject(self::USER_ID_A));

        $employee->unlinkUser();

        self::assertNull($employee->userId());
        self::assertFalse($employee->hasUser());
    }

    public function testCanLinkUserReturnsTrueWhenNoUser(): void
    {
        $employee = $this->createEmployee();

        self::assertTrue($employee->canLinkUser(new UserIdValueObject(self::USER_ID_A)));
    }

    public function testCanLinkUserReturnsTrueForSameUser(): void
    {
        $employee = $this->createEmployee();
        $userId = new UserIdValueObject(self::USER_ID_A);
        $employee->linkUser($userId);

        self::assertTrue($employee->canLinkUser($userId));
    }

    public function testCanLinkUserReturnsFalseForDifferentUser(): void
    {
        $employee = $this->createEmployee();
        $employee->linkUser(new UserIdValueObject(self::USER_ID_A));

        self::assertFalse($employee->canLinkUser(new UserIdValueObject(self::USER_ID_B)));
    }

    public function testHasUserReturnsFalseInitially(): void
    {
        $employee = $this->createEmployee();

        self::assertFalse($employee->hasUser());
    }

    public function testLinkUnlinkRoundTrip(): void
    {
        $employee = $this->createEmployee();
        $userId = new UserIdValueObject(self::USER_ID_A);

        $employee->linkUser($userId);
        self::assertTrue($employee->hasUser());

        $employee->unlinkUser();
        self::assertFalse($employee->hasUser());

        $employee->linkUser($userId);
        self::assertTrue($employee->hasUser());
        self::assertSame(self::USER_ID_A, (string) $employee->userId());
    }
}
