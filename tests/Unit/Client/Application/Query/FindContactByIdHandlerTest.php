<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Application\Query\FindContactById\FindContactByIdHandler;
use App\Client\Application\Query\FindContactById\FindContactByIdQuery;
use App\Client\Application\Query\Response\ContactResponse;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use PHPUnit\Framework\TestCase;

final class FindContactByIdHandlerTest extends TestCase
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

    public function testReturnsContactResponse(): void
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($this->makeContact());

        $handler = new FindContactByIdHandler($contactRepo);
        $response = ($handler)(new FindContactByIdQuery(contactId: self::CONTACT_ID));

        self::assertInstanceOf(ContactResponse::class, $response);
        self::assertSame(self::CONTACT_ID, $response->id);
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn(null);

        $handler = new FindContactByIdHandler($contactRepo);

        $this->expectException(ContactNotFoundException::class);
        $this->expectExceptionMessage('Contacto con ID "' . self::CONTACT_ID . '" no encontrado.');

        ($handler)(new FindContactByIdQuery(contactId: self::CONTACT_ID));
    }
}
