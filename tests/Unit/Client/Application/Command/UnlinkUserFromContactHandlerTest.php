<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UnlinkUserFromContact\UnlinkUserFromContactCommand;
use App\Client\Application\Command\UnlinkUserFromContact\UnlinkUserFromContactHandler;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Events\ContactUserUnlinked;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class UnlinkUserFromContactHandlerTest extends TestCase
{
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAX';

    public function testUnlinksUserFromContactSuccessfully(): void
    {
        $contact = Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('john@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );
        $contact->linkUser(new UserIdValueObject(self::USER_ID));

        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn($contact);
        $repo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ContactUserUnlinked::class));

        $handler = new UnlinkUserFromContactHandler($repo, $eventPublisher);
        ($handler)(new UnlinkUserFromContactCommand(contactId: self::CONTACT_ID));

        self::assertNull($contact->userId());
    }

    public function testDoesNothingWhenContactNotFound(): void
    {
        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $handler = new UnlinkUserFromContactHandler($repo, $eventPublisher);
        ($handler)(new UnlinkUserFromContactCommand(contactId: self::CONTACT_ID));
    }
}
