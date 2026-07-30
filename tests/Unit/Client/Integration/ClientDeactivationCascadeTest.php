<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Integration;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Events\ContactDeactivated;
use App\Client\Domain\Events\ContactReactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Client\Infrastructure\Messenger\Handler\ContactStatusCascadeHandler;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Integration test (unit-level with mocks) for the Contact → User
 * status cascade across Client and Identity Bounded Contexts.
 *
 * G-01/G-03: When a Contact is deactivated/reactivated, the linked
 * User in Identity BC must follow the same state transition.
 */
#[Group('integration')]
#[Group('cross-bc')]
final class ClientDeactivationCascadeTest extends TestCase
{
    private const CLIENT_ID   = '01J8XQ9W6K0000000000000001';
    private const CONTACT_ID  = '01J8XQ9W6K0000000000000002';
    private const USER_ID     = '01JCPX8YZ10000000000000002';
    private const ROLE_ID     = '01JCPX8YZ10000000000000003';

    private function createContact(?UserIdValueObject $userId = null): Contact
    {
        return Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'Andrea',
            lastName: 'López',
            email: new ContactEmailValueObject('andrea.lopez@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0998877665'),
            role: new ContactRoleValueObject('Contacto Principal'),
            userId: $userId,
        );
    }

    private function createActiveUser(): User
    {
        $user = User::register(
            id: new UserIdValueObject(self::USER_ID),
            username: new UsernameValueObject('alopez'),
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
        ContactRepositoryInterface $contactRepo,
        UserRepositoryInterface $userRepo,
    ): ContactStatusCascadeHandler {
        return new ContactStatusCascadeHandler(
            contactRepository: $contactRepo,
            userRepository: $userRepo,
        );
    }

    // ─── Happy path: Contact deactivated → User deactivated ──────────

    public function testDeactivatesLinkedUserWhenContactDeactivated(): void
    {
        $user = $this->createActiveUser();
        $contact = $this->createContact(new UserIdValueObject(self::USER_ID));

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->once())->method('save')->with($user);

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactDeactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));

        self::assertSame(UserStatus::INACTIVE, $user->status());
    }

    // ─── Happy path: Contact reactivated → User reactivated ──────────

    public function testReactivatesLinkedUserWhenContactReactivated(): void
    {
        $user = $this->createInactiveUser();
        $contact = $this->createContact(new UserIdValueObject(self::USER_ID));

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->once())->method('save')->with($user);

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactReactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));

        self::assertSame(UserStatus::ACTIVE, $user->status());
    }

    // ─── Edge case: contact without linked user ──────────────────────

    public function testDoesNothingWhenContactHasNoLinkedUser(): void
    {
        $contact = $this->createContact(null); // No userId

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('findById');
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactDeactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    // ─── Edge case: contact not found ────────────────────────────────

    public function testDoesNothingWhenContactNotFound(): void
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn(null);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('findById');
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactDeactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    // ─── Edge case: linked user not found ────────────────────────────

    public function testDoesNothingWhenLinkedUserNotFound(): void
    {
        $contact = $this->createContact(new UserIdValueObject(self::USER_ID));

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn(null);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactDeactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    // ─── Edge case: user already in target state ─────────────────────

    public function testDoesNothingWhenLinkedUserAlreadyInactive(): void
    {
        $user = $this->createInactiveUser();
        $contact = $this->createContact(new UserIdValueObject(self::USER_ID));

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactDeactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

    public function testDoesNothingWhenLinkedUserAlreadyActiveOnReactivate(): void
    {
        $user = $this->createActiveUser();
        $contact = $this->createContact(new UserIdValueObject(self::USER_ID));

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')
            ->with($this->isInstanceOf(ContactIdValueObject::class))
            ->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')
            ->with($this->isInstanceOf(UserIdValueObject::class))
            ->willReturn($user);

        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($contactRepo, $userRepo);

        $event = new ContactReactivated(contactId: self::CONTACT_ID, clientId: self::CLIENT_ID);
        $handler(DomainEventMessage::fromEvent($event));
    }

}
