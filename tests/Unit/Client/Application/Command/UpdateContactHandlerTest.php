<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateContact\UpdateContactCommand;
use App\Client\Application\Command\UpdateContact\UpdateContactHandler;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Events\ContactUpdated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class UpdateContactHandlerTest extends TestCase
{
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testUpdatesContactSuccessfully(): void
    {
        $contact = Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('old@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );

        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn($contact);
        $repo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ContactUpdated::class));

        $handler = new UpdateContactHandler($repo, $eventPublisher);
        ($handler)(new UpdateContactCommand(
            contactId: self::CONTACT_ID,
            firstName: 'Jane',
            lastName: 'Smith',
            email: 'jane@test.com',
            phoneNumber: '0988888888',
            role: 'supervisor',
        ));
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateContactHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(ContactNotFoundException::class);
        ($handler)(new UpdateContactCommand(
            contactId: self::CONTACT_ID,
            firstName: 'Jane',
            lastName: 'Smith',
            email: 'jane@test.com',
            phoneNumber: '0988888888',
            role: 'supervisor',
        ));
    }
}
