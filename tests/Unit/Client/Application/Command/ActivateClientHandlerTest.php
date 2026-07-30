<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ActivateClient\ActivateClientCommand;
use App\Client\Application\Command\ActivateClient\ActivateClientHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Exceptions\ClientStatusException;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;

final class ActivateClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const VALID_RUC = '1712345675001';

    private function makeInactiveClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject(self::VALID_RUC),
            status: ClientStatus::INACTIVE,
        );
    }

    public function testActivatesClientSuccessfully(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);

        $client = $this->makeInactiveClient();
        $clientRepo->method('findById')->willReturn($client);

        $savedClient = null;
        $clientRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $c) use (&$savedClient): void {
                $savedClient = $c;
            });

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new ActivateClientHandler($clientRepo, $transactionManager);
        ($handler)(new ActivateClientCommand(clientId: self::CLIENT_ID));

        self::assertNotNull($savedClient);
        self::assertTrue($savedClient->isActive());
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);

        $clientRepo->method('findById')->willReturn(null);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new ActivateClientHandler($clientRepo, $transactionManager);

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Cliente con ID "' . self::CLIENT_ID . '" no encontrado.');

        ($handler)(new ActivateClientCommand(clientId: self::CLIENT_ID));
    }

    public function testThrowsWhenClientAlreadyActive(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);

        $client = Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject(self::VALID_RUC),
            status: ClientStatus::ACTIVE,
        );
        $clientRepo->method('findById')->willReturn($client);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new ActivateClientHandler($clientRepo, $transactionManager);

        $this->expectException(ClientStatusException::class);
        $this->expectExceptionMessage('El cliente ya se encuentra activo.');

        ($handler)(new ActivateClientCommand(clientId: self::CLIENT_ID));
    }
}
