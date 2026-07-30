<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Exceptions\UserAlreadyBoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeCommand;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Service\EmployeeUserInvariantService;
use App\Organization\Domain\Events\EmployeeUserLinked;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Exceptions\EmployeeAlreadyHasUserException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LinkUserToEmployeeHandlerTest extends TestCase
{
    private const USER_ID     = '01JCPX8XYZ0000000000000001';
    private const EMPLOYEE_ID = '01JCPX8XYZ0000000000000002';
    private const ROLE_ID     = '01JCPX8XYZ0000000000000003';

    private function createTestUser(): User
    {
        return User::register(
            id:       new UserIdValueObject(self::USER_ID),
            username: new UsernameValueObject('jperez'),
            password: new HashedPasswordValueObject('$2y$12hashedpassword'),
            roleId:   new RoleIdValueObject(self::ROLE_ID),
        );
    }

    private function createTestEmployee(): Employee
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

    public function testLinksUserToEmployeeSuccessfully(): void
    {
        $employee = $this->createTestEmployee();
        $user = $this->createTestUser();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);
        $bindingChecker->expects($this->once())->method('ensureNotBound');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(EmployeeUserLinked::class));

        $employeeRepo->expects($this->once())->method('save')->with($employee);
        $userRepo->expects($this->once())->method('save')->with($user);

        $handler = new LinkUserToEmployeeHandler(
            $employeeRepo,
            $userRepo,
            new EmployeeUserInvariantService($bindingChecker),
            $publisher,
        );

        ($handler)(new LinkUserToEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
            userId: self::USER_ID,
        ));

        self::assertSame(self::USER_ID, (string) $employee->userId());
        self::assertTrue($user->hasEmployeeLinked());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new LinkUserToEmployeeHandler(
            $employeeRepo,
            $userRepo,
            new EmployeeUserInvariantService($bindingChecker),
            $publisher,
        );

        $this->expectException(EmployeeNotFoundException::class);
        $this->expectExceptionMessage(self::EMPLOYEE_ID);

        ($handler)(new LinkUserToEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
            userId: self::USER_ID,
        ));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $employee = $this->createTestEmployee();
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(null);

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new LinkUserToEmployeeHandler(
            $employeeRepo,
            $userRepo,
            new EmployeeUserInvariantService($bindingChecker),
            $publisher,
        );

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage(self::USER_ID);

        ($handler)(new LinkUserToEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
            userId: self::USER_ID,
        ));
    }

    public function testThrowsWhenEmployeeAlreadyHasUser(): void
    {
        $employee = $this->createTestEmployee();
        $employee->linkUser(new UserIdValueObject(self::USER_ID));

        $user = $this->createTestUser();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new LinkUserToEmployeeHandler(
            $employeeRepo,
            $userRepo,
            new EmployeeUserInvariantService($bindingChecker),
            $publisher,
        );

        $this->expectException(EmployeeAlreadyHasUserException::class);

        ($handler)(new LinkUserToEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
            userId: self::USER_ID,
        ));
    }

    public function testThrowsWhenUserAlreadyBound(): void
    {
        $employee = $this->createTestEmployee();
        $user = $this->createTestUser();

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn($employee);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);
        $bindingChecker->expects($this->once())
            ->method('ensureNotBound')
            ->willThrowException(
                UserAlreadyBoundException::withUserId(self::USER_ID, 'otro empleado'),
            );

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new LinkUserToEmployeeHandler(
            $employeeRepo,
            $userRepo,
            new EmployeeUserInvariantService($bindingChecker),
            $publisher,
        );

        $this->expectException(UserAlreadyBoundException::class);

        ($handler)(new LinkUserToEmployeeCommand(
            employeeId: self::EMPLOYEE_ID,
            userId: self::USER_ID,
        ));
    }
}
