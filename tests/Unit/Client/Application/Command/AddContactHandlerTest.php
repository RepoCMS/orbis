<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\AddContact\AddContactCommand;
use App\Client\Application\Command\AddContact\AddContactHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AddContactHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FC1';

    private function makeActiveClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
            'Address',
            ClientStatus::ACTIVE,
        );
    }

    private function makeInactiveClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
            'Address',
            ClientStatus::INACTIVE,
        );
    }

    public function testAddsContactToActiveClientSuccessfully(): void
    {
        $client = $this->makeActiveClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::CONTACT_ID);

        $savedContact = null;
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Contact $c) use (&$savedContact): void {
                $savedContact = $c;
            });

        $handler = new AddContactHandler($clientRepo, $contactRepo, $idGenerator);
        ($handler)(new AddContactCommand(
            clientId: self::CLIENT_ID,
            firstName: 'Juan',
            lastName: 'Perez',
            email: 'juan@empresa.com',
            phoneNumber: '0991234567',
            role: 'Técnico de ATMs',
        ));

        self::assertNotNull($savedContact);
        self::assertSame(self::CONTACT_ID, (string) $savedContact->id());
        self::assertSame('Juan', $savedContact->firstName());
        self::assertSame('Perez', $savedContact->lastName());
        self::assertSame('juan@empresa.com', (string) $savedContact->email());
        self::assertSame('0991234567', (string) $savedContact->phoneNumber());
        self::assertTrue($savedContact->isActive());
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new AddContactHandler($clientRepo, $contactRepo, $idGenerator);

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage(self::CLIENT_ID);

        ($handler)(new AddContactCommand(
            clientId: self::CLIENT_ID,
            firstName: 'Juan',
            lastName: 'Perez',
            email: 'juan@empresa.com',
            phoneNumber: '0991234567',
            role: 'Técnico',
        ));
    }

    public function testAddsContactToInactiveClientSuccessfully(): void
    {
        $client = $this->makeInactiveClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::CONTACT_ID);

        $savedContact = null;
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Contact $c) use (&$savedContact): void {
                $savedContact = $c;
            });

        $handler = new AddContactHandler($clientRepo, $contactRepo, $idGenerator);

        ($handler)(new AddContactCommand(
            clientId: self::CLIENT_ID,
            firstName: 'Juan',
            lastName: 'Perez',
            email: 'juan@empresa.com',
            phoneNumber: '0991234567',
            role: 'Técnico',
        ));

        self::assertNotNull($savedContact);
    }
}
