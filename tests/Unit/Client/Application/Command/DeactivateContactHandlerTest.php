<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\DeactivateContact\DeactivateContactCommand;
use App\Client\Application\Command\DeactivateContact\DeactivateContactHandler;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Enums\ContactStatus;
use App\Client\Domain\Events\ContactDeactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class DeactivateContactHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CONTACT_ID_1 = '01ARZ3NDEKTSV4RRFFQ69G5FB1';
    private const CONTACT_ID_2 = '01ARZ3NDEKTSV4RRFFQ69G5FB2';
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FC1';

    private function makeActiveContact(string $id): Contact
    {
        return Contact::create(
            new ContactIdValueObject($id),
            new ClientIdValueObject(self::CLIENT_ID),
            'John',
            'Doe',
            new ContactEmailValueObject('john@example.com'),
            new ContactPhoneNumberValueObject('0999999999'),
            new ContactRoleValueObject('Técnico'),
        );
    }

    public function testDeactivatesContactSuccessfully(): void
    {
        $contact = $this->makeActiveContact(self::CONTACT_ID_1);

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);
        $contactRepo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->callback(function (ContactDeactivated $event): bool {
                return $event->contactId === self::CONTACT_ID_1
                    && $event->clientId === self::CLIENT_ID;
            }));

        $handler = new DeactivateContactHandler($contactRepo, $eventPublisher);
        ($handler)(new DeactivateContactCommand(self::CONTACT_ID_1));

        self::assertFalse($contact->isActive());
        self::assertSame(ContactStatus::INACTIVE, $contact->status());
    }

    public function testDeactivatesContactWithUserLinked(): void
    {
        $contact = $this->makeActiveContact(self::CONTACT_ID_1);
        $contact->linkUser(new UserIdValueObject(self::USER_ID));

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);
        $contactRepo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->callback(function (ContactDeactivated $event): bool {
                return $event->contactId === self::CONTACT_ID_1
                    && $event->clientId === self::CLIENT_ID;
            }));

        $handler = new DeactivateContactHandler($contactRepo, $eventPublisher);
        ($handler)(new DeactivateContactCommand(self::CONTACT_ID_1));

        self::assertFalse($contact->isActive());
        self::assertSame(ContactStatus::INACTIVE, $contact->status());
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn(null);
        $contactRepo->expects($this->never())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->never())->method('publish');

        $handler = new DeactivateContactHandler($contactRepo, $eventPublisher);

        $this->expectException(ContactNotFoundException::class);
        $this->expectExceptionMessage(self::CONTACT_ID_1);

        ($handler)(new DeactivateContactCommand(self::CONTACT_ID_1));
    }

    public function testThrowsWhenContactAlreadyInactive(): void
    {
        $contact = $this->makeActiveContact(self::CONTACT_ID_1);
        $contact->deactivate();

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->never())->method('publish');

        $handler = new DeactivateContactHandler($contactRepo, $eventPublisher);

        $this->expectException(\App\Client\Domain\Exceptions\ContactStatusException::class);
        $this->expectExceptionMessage('El contacto ya se encuentra inactivo.');

        ($handler)(new DeactivateContactCommand(self::CONTACT_ID_1));
    }
}
