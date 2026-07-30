<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\LinkUserToContact\LinkUserToContactCommand;
use App\Client\Application\Command\LinkUserToContact\LinkUserToContactHandler;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class LinkUserToContactHandlerTest extends TestCase
{
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAX';

    private function makeContact(): Contact
    {
        return Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('john@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );
    }

    public function testLinksUserToContactSuccessfully(): void
    {
        $contact = $this->makeContact();

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);
        $contactRepo->expects($this->once())->method('save');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(
            User::register(
                new UserIdValueObject(self::USER_ID),
                new UsernameValueObject('john.doe'),
                new HashedPasswordValueObject('hashed'),
                new RoleIdValueObject(self::USER_ID),
            ),
        );

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);
        $bindingChecker->expects($this->once())->method('ensureNotBound');

        $handler = new LinkUserToContactHandler($contactRepo, $userRepo, $bindingChecker);
        ($handler)(new LinkUserToContactCommand(
            contactId: self::CONTACT_ID,
            userId: self::USER_ID,
        ));

        self::assertNotNull($contact->userId());
        self::assertSame(self::USER_ID, (string) $contact->userId());
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn(null);

        $handler = new LinkUserToContactHandler(
            $contactRepo,
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(UserBindingCheckerInterface::class),
        );

        $this->expectException(ContactNotFoundException::class);
        $this->expectExceptionMessage(self::CONTACT_ID);

        ($handler)(new LinkUserToContactCommand(
            contactId: self::CONTACT_ID,
            userId: self::USER_ID,
        ));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $contact = $this->makeContact();

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(null);

        $handler = new LinkUserToContactHandler(
            $contactRepo,
            $userRepo,
            $this->createMock(UserBindingCheckerInterface::class),
        );

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage(self::USER_ID);

        ($handler)(new LinkUserToContactCommand(
            contactId: self::CONTACT_ID,
            userId: self::USER_ID,
        ));
    }
}
