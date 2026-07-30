<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Service;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Support\Application\Service\SparePartViewScopeResolver;
use PHPUnit\Framework\TestCase;

final class SparePartViewScopeResolverTest extends TestCase
{
    private const USER_ID = '01HZ0000000000000000000001';
    private const EMPLOYEE_ID = '01HZ0000000000000000000002';

    public function testReturnsNullWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn(null);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);

        $resolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);
        $result = $resolver->resolve(self::USER_ID);

        self::assertNull($result);
    }

    public function testReturnsNullForNonTechnician(): void
    {
        $employee = Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1234567890'),
            firstName: 'Admin',
            lastName: 'User Admin',
            email: new EmployeeEmailValueObject('admin@example.com'),
            phoneNumber: '0987654321',
            hiredAt: new \DateTimeImmutable(),
            userId: null,
            profile: EmployeeProfile::ADMINISTRATIVE,
        );

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);

        $resolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);
        $result = $resolver->resolve(self::USER_ID);

        self::assertNull($result);
    }

    public function testReturnsScopeWithEmployeeIdForTechnician(): void
    {
        $employee = Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1234567890'),
            firstName: 'Technician',
            lastName: 'User Technician',
            email: new EmployeeEmailValueObject('tech@example.com'),
            phoneNumber: '0987654322',
            hiredAt: new \DateTimeImmutable(),
            userId: null,
            profile: EmployeeProfile::TECHNICIAN,
        );

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByEmployeeId')->willReturn([]);

        $resolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);
        $result = $resolver->resolve(self::USER_ID);

        self::assertNotNull($result);
        self::assertSame(self::EMPLOYEE_ID, $result->employeeId);
        self::assertNull($result->locationId);
    }

    public function testReturnsScopeWithBackpackLocationId(): void
    {
        $employee = Employee::hire(
            id: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            identificationNumber: new EmployeeIdentificationNumberValueObject('1234567890'),
            firstName: 'Technician',
            lastName: 'User Technician',
            email: new EmployeeEmailValueObject('tech@example.com'),
            phoneNumber: '0987654322',
            hiredAt: new \DateTimeImmutable(),
            userId: null,
            profile: EmployeeProfile::TECHNICIAN,
        );

        $backpackLocation = Location::create(
            id: new LocationIdValueObject('01HZ0000000000000000000003'),
            name: new LocationNameValueObject('Mochila Técnico'),
            city: 'Ciudad',
            address: 'Dirección',
            type: LocationType::BACKPACK,
        );

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findByUserId')->willReturn($employee);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByEmployeeId')->willReturn([$backpackLocation]);

        $resolver = new SparePartViewScopeResolver($employeeRepo, $locationRepo);
        $result = $resolver->resolve(self::USER_ID);

        self::assertNotNull($result);
        self::assertSame(self::EMPLOYEE_ID, $result->employeeId);
        self::assertSame('01HZ0000000000000000000003', $result->locationId);
    }
}
