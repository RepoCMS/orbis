<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CreateEmployee\CreateEmployeeCommand;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeHandler;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\EmploymentPeriod;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Events\EmployeeCreated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmploymentPeriodRepositoryInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateEmployeeHandlerTest extends TestCase
{
    private const GENERATED_ID  = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const ID_NUMBER     = '1710034065';
    private const FIRST_NAME    = 'Juan Carlos';
    private const LAST_NAME     = 'Pérez García';
    private const EMAIL         = 'juan.perez@example.com';
    private const PHONE         = '0998765432';
    private const HIRED_AT      = '2024-01-15';
    private const PROFILE       = 'technician';

    public function testCreatesEmployeeSuccessfully(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $employmentPeriodRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EmploymentPeriod::class));

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(EmployeeCreated::class));

        $handler = new CreateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);

        $result = ($handler)(new CreateEmployeeCommand(
            identificationNumber: self::ID_NUMBER,
            firstName:            self::FIRST_NAME,
            lastName:             self::LAST_NAME,
            email:                self::EMAIL,
            phoneNumber:          self::PHONE,
            hiredAt:              self::HIRED_AT,
            profile:              self::PROFILE,
        ));

        self::assertSame(self::GENERATED_ID, $result);
        self::assertNotNull($savedEmployee);
        self::assertSame(self::FIRST_NAME, $savedEmployee->firstName());
        self::assertSame(self::LAST_NAME, $savedEmployee->lastName());
        self::assertSame(self::EMAIL, (string) $savedEmployee->email());
        self::assertSame(self::PHONE, $savedEmployee->phoneNumber());
        self::assertTrue($savedEmployee->isTechnician());
    }

    public function testThrowsWhenInvalidDateFormat(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $employmentPeriodRepo->expects($this->any())
            ->method('save')
            ->with($this->isInstanceOf(EmploymentPeriod::class));

        $handler = new CreateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fecha de contratación inválida');

        ($handler)(new CreateEmployeeCommand(
            identificationNumber: self::ID_NUMBER,
            firstName:            self::FIRST_NAME,
            lastName:             self::LAST_NAME,
            email:                self::EMAIL,
            phoneNumber:          self::PHONE,
            hiredAt:              'not-a-date',
            profile:              self::PROFILE,
        ));
    }

    public function testThrowsWhenIdentificationNumberIsEmpty(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $employmentPeriodRepo->expects($this->any())
            ->method('save')
            ->with($this->isInstanceOf(EmploymentPeriod::class));

        $handler = new CreateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);

        $this->expectException(\App\Organization\Domain\Exceptions\EmployeeIdentificationNumberException::class);
        $this->expectExceptionMessage('El número de identificación no puede estar vacío.');

        ($handler)(new CreateEmployeeCommand(
            identificationNumber: '',
            firstName:            self::FIRST_NAME,
            lastName:             self::LAST_NAME,
            email:                self::EMAIL,
            phoneNumber:          self::PHONE,
            hiredAt:              self::HIRED_AT,
            profile:              self::PROFILE,
        ));
    }

    public function testThrowsWhenEmailIsInvalid(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $employmentPeriodRepo->expects($this->any())
            ->method('save')
            ->with($this->isInstanceOf(EmploymentPeriod::class));

        $handler = new CreateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);

        $this->expectException(\App\Organization\Domain\Exceptions\EmployeeEmailException::class);
        $this->expectExceptionMessage('Formato de correo electrónico inválido.');

        ($handler)(new CreateEmployeeCommand(
            identificationNumber: self::ID_NUMBER,
            firstName:            self::FIRST_NAME,
            lastName:             self::LAST_NAME,
            email:                'not-an-email',
            phoneNumber:          self::PHONE,
            hiredAt:              self::HIRED_AT,
            profile:              self::PROFILE,
        ));
    }

    public function testCreatesEmployeeWithNoUserProvisioning(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employmentPeriodRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedEmployee = null;
        $employeeRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Employee $e) use (&$savedEmployee): void {
                $savedEmployee = $e;
            });

        $employmentPeriodRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EmploymentPeriod::class));

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(EmployeeCreated::class));

        $handler = new CreateEmployeeHandler($employeeRepo, $employmentPeriodRepo, $idGenerator, $publisher);

        $result = ($handler)(new CreateEmployeeCommand(
            identificationNumber: self::ID_NUMBER,
            firstName:            self::FIRST_NAME,
            lastName:             self::LAST_NAME,
            email:                self::EMAIL,
            phoneNumber:          self::PHONE,
            hiredAt:              self::HIRED_AT,
            profile:              self::PROFILE,
        ));

        self::assertSame(self::GENERATED_ID, $result);
        self::assertNotNull($savedEmployee);
        self::assertSame(EmployeeStatus::ACTIVE, $savedEmployee->status());
        self::assertNull($savedEmployee->userId());
    }
}
