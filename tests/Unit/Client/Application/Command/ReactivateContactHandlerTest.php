<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ReactivateContact\ReactivateContactCommand;
use App\Client\Application\Command\ReactivateContact\ReactivateContactHandler;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Enums\ContactStatus;
use App\Client\Domain\Events\ContactReactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class ReactivateContactHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FB1';

    private function makeInactiveContact(): Contact
    {
        $contact = Contact::create(
            new ContactIdValueObject(self::CONTACT_ID),
            new ClientIdValueObject(self::CLIENT_ID),
            'John',
            'Doe',
            new ContactEmailValueObject('john@example.com'),
            new ContactPhoneNumberValueObject('0999999999'),
            new ContactRoleValueObject('Técnico'),
        );
        $contact->deactivate();

        return $contact;
    }

    public function testReactivatesInactiveContactSuccessfully(): void
    {
        $contact = $this->makeInactiveContact();

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);

        $savedContact = null;
        $contactRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Contact $c) use (&$savedContact): void {
                $savedContact = $c;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->callback(function (ContactReactivated $event): bool {
                return $event->contactId === self::CONTACT_ID
                    && $event->clientId === self::CLIENT_ID;
            }));

        $handler = new ReactivateContactHandler($contactRepo, $eventPublisher);
        ($handler)(new ReactivateContactCommand(self::CONTACT_ID));

        self::assertNotNull($savedContact);
        self::assertTrue($savedContact->isActive());
        self::assertSame(ContactStatus::ACTIVE, $savedContact->status());
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn(null);
        $contactRepo->expects($this->never())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->never())->method('publish');

        $handler = new ReactivateContactHandler($contactRepo, $eventPublisher);

        $this->expectException(ContactNotFoundException::class);
        $this->expectExceptionMessage(self::CONTACT_ID);

        ($handler)(new ReactivateContactCommand(self::CONTACT_ID));
    }
}
