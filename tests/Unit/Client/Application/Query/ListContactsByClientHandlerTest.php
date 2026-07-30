<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\ListContactsByClient\ListContactsByClientHandler;
use App\Client\Application\Query\ListContactsByClient\ListContactsByClientQuery;
use App\Client\Application\Query\Response\ContactResponse;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use PHPUnit\Framework\TestCase;

final class ListContactsByClientHandlerTest extends TestCase
{
    public function testReturnsContactResponses(): void
    {
        $contact = Contact::create(
            id: new ContactIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            clientId: new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('john@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );

        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$contact]);

        $handler = new ListContactsByClientHandler($repo);
        $result = ($handler)(new ListContactsByClientQuery());

        self::assertCount(1, $result);
        self::assertInstanceOf(ContactResponse::class, $result[0]);
    }

    public function testReturnsEmptyArray(): void
    {
        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListContactsByClientHandler($repo);
        $result = ($handler)(new ListContactsByClientQuery());

        self::assertCount(0, $result);
    }
}
