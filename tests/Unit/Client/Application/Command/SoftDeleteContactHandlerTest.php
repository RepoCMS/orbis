<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactCommand;
use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactHandler;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Enums\ContactStatus;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;

final class SoftDeleteContactHandlerTest extends TestCase
{
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    private function makeContact(): Contact
    {
        return Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('test@example.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );
    }

    public function testSoftDeletesContactSuccessfully(): void
    {
        $contact = $this->makeContact();

        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn($contact);
        $saved = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($c) use (&$saved): void {
                $saved = $c;
            });

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new SoftDeleteContactHandler($repo, $transactionManager);
        ($handler)(new SoftDeleteContactCommand(contactId: self::CONTACT_ID));

        self::assertNotNull($saved->deletedAt());
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new SoftDeleteContactHandler($repo, $transactionManager);

        $this->expectException(ContactNotFoundException::class);
        ($handler)(new SoftDeleteContactCommand(contactId: self::CONTACT_ID));
    }
}
