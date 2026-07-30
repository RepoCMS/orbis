<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Infrastructure\Messenger\Handler;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Organization\Domain\Events\EmployeeDeleted;
use App\Organization\Domain\Events\EmployeeReactivated;
use App\Organization\Infrastructure\Messenger\Handler\EmployeeStatusCascadeHandler;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class EmployeeStatusCascadeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01JCPX8XYZ0000000000000001';
    private const USER_ID = '01JCPX8XYZ0000000000000002';

    private function createActiveUser(): User
    {
        $user = User::register(
            id: new UserIdValueObject(self::USER_ID),
            username: new UsernameValueObject('jperez'),
            password: new HashedPasswordValueObject('$2y$12hash'),
            roleId: new RoleIdValueObject('01JCPX8XYZ0000000000000003'),
        );
        $user->activate();

        return $user;
    }

    private function createInactiveUser(): User
    {
        $user = $this->createActiveUser();
        $user->deactivate();

        return $user;
    }

    private function createHandler(
        UserRepositoryInterface $userRepo,
    ): EmployeeStatusCascadeHandler {
        return new EmployeeStatusCascadeHandler(
            userRepository: $userRepo,
            logger: new NullLogger(),
        );
    }

    public function testDeactivatesUserWhenEmployeeDeactivated(): void
    {
        $user = $this->createActiveUser();
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->once())->method('save')->with($user);

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));

        self::assertSame(UserStatus::INACTIVE, $user->status());
    }

    public function testReactivatesUserWhenEmployeeReactivated(): void
    {
        $user = $this->createInactiveUser();
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->once())->method('save')->with($user);

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeReactivated(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));

        self::assertSame(UserStatus::ACTIVE, $user->status());
    }

    public function testSoftDeletesUserWhenEmployeeDeleted(): void
    {
        $user = $this->createActiveUser();
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->once())->method('save')->with($user);

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeleted(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));

        self::assertTrue($user->isDeleted());
    }

    public function testDoesNothingWhenEventHasNoUserId(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: null);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenUserNotFound(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn(null);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenUserAlreadyInactive(): void
    {
        $user = $this->createInactiveUser();
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenUserAlreadyActive(): void
    {
        $user = $this->createActiveUser();
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeReactivated(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenUserAlreadyDeleted(): void
    {
        $user = $this->createActiveUser();
        $user->softDelete();

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeleted(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }
}
