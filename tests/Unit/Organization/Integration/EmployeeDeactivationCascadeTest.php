<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Integration;

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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Integration test (unit-level with mocks) for the Employee → User
 * status cascade across Organization and Identity Bounded Contexts.
 *
 * G-01: Dual cascade — Employee deactivation/reactivation/deletion
 * must propagate to the linked User in Identity BC.
 */
#[Group('integration')]
#[Group('cross-bc')]
final class EmployeeDeactivationCascadeTest extends TestCase
{
    private const EMPLOYEE_ID = '01JCPX8YZ10000000000000001';
    private const USER_ID = '01JCPX8YZ10000000000000002';
    private const ROLE_ID = '01JCPX8YZ10000000000000003';

    private function createActiveUser(): User
    {
        $user = User::register(
            id: new UserIdValueObject(self::USER_ID),
            username: new UsernameValueObject('jcascade'),
            password: new HashedPasswordValueObject('$2y$12hash'),
            roleId: new RoleIdValueObject(self::ROLE_ID),
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

    // ─── Happy path ──────────────────────────────────────────────────

    public function testDeactivatesLinkedUserWhenEmployeeDeactivated(): void
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

    public function testReactivatesLinkedUserWhenEmployeeReactivated(): void
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

    public function testSoftDeletesLinkedUserWhenEmployeeDeleted(): void
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

    // ─── Edge cases: no userId ───────────────────────────────────────

    public function testDoesNothingWhenEmployeeDeactivatedEventHasNoUserId(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('findById');
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeactivated(employeeId: self::EMPLOYEE_ID, userId: null);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenEmployeeReactivatedEventHasNoUserId(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('findById');
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeReactivated(employeeId: self::EMPLOYEE_ID, userId: null);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenEmployeeDeletedEventHasNoUserId(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('findById');
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeleted(employeeId: self::EMPLOYEE_ID, userId: null);
        $handler(DomainEventMessage::fromEvent($event));
    }

    // ─── Edge cases: user not found ──────────────────────────────────

    public function testDoesNothingWhenLinkedUserNotFoundOnDeactivate(): void
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

    public function testDoesNothingWhenLinkedUserNotFoundOnReactivate(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn(null);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeReactivated(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenLinkedUserNotFoundOnDelete(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn(null);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo);

        $event = new EmployeeDeleted(employeeId: self::EMPLOYEE_ID, userId: self::USER_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    // ─── Edge cases: already in target state ─────────────────────────

    public function testDoesNothingWhenLinkedUserAlreadyInactive(): void
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

    public function testDoesNothingWhenLinkedUserAlreadyActiveOnReactivate(): void
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

    public function testDoesNothingWhenLinkedUserAlreadyDeleted(): void
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
