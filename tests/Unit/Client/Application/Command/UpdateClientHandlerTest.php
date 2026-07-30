<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateClient\UpdateClientCommand;
use App\Client\Application\Command\UpdateClient\UpdateClientHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;

final class UpdateClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const VALID_RUC = '1712345675001';

    private function makeClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Original Name'),
            new ClientRucValueObject(self::VALID_RUC),
            'Original Address',
            ClientStatus::ACTIVE,
        );
    }

    public function testUpdatesClientSuccessfully(): void
    {
        $client = $this->makeClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $savedClient = null;
        $clientRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $c) use (&$savedClient): void {
                $savedClient = $c;
            });

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new UpdateClientHandler($clientRepo, $transactionManager);
        ($handler)(new UpdateClientCommand(
            clientId: self::CLIENT_ID,
            name: 'Updated Name',
            address: 'Updated Address',
        ));

        self::assertSame('Updated Name', (string) $savedClient->name());
        self::assertSame('Updated Address', $savedClient->address());
    }

    public function testUpdatesClientWithoutAddress(): void
    {
        $client = $this->makeClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $savedClient = null;
        $clientRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $c) use (&$savedClient): void {
                $savedClient = $c;
            });

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new UpdateClientHandler($clientRepo, $transactionManager);
        ($handler)(new UpdateClientCommand(
            clientId: self::CLIENT_ID,
            name: 'Name Only',
        ));

        self::assertSame('Name Only', (string) $savedClient->name());
        self::assertNull($savedClient->address());
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new UpdateClientHandler($clientRepo, $transactionManager);

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Cliente con ID "' . self::CLIENT_ID . '" no encontrado.');

        ($handler)(new UpdateClientCommand(
            clientId: self::CLIENT_ID,
            name: 'Should Fail',
        ));
    }
}
