<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\DeactivateClient\DeactivateClientCommand;
use App\Client\Application\Command\DeactivateClient\DeactivateClientHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Exceptions\ClientStatusException;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserHandler;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeactivateClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

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

    private function createDeactivateUserHandler(): DeactivateUserHandler
    {
        return new DeactivateUserHandler(
            $this->createMock(UserRepositoryInterface::class),
        );
    }

    public function testDeactivatesClientSuccessfully(): void
    {
        $client = $this->makeActiveClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findByClientId')->willReturn([]);

        $handler = new DeactivateClientHandler($clientRepo, $contactRepo, $this->createDeactivateUserHandler());
        ($handler)(new DeactivateClientCommand(self::CLIENT_ID));

        self::assertSame(ClientStatus::INACTIVE, $client->status());
        self::assertFalse($client->isActive());
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);

        $handler = new DeactivateClientHandler($clientRepo, $contactRepo, $this->createDeactivateUserHandler());

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage(self::CLIENT_ID);

        ($handler)(new DeactivateClientCommand(self::CLIENT_ID));
    }

    public function testThrowsWhenClientAlreadyInactive(): void
    {
        $client = $this->makeInactiveClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);

        $handler = new DeactivateClientHandler($clientRepo, $contactRepo, $this->createDeactivateUserHandler());

        $this->expectException(ClientStatusException::class);

        ($handler)(new DeactivateClientCommand(self::CLIENT_ID));
    }
}
