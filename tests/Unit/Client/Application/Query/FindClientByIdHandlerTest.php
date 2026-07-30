<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Query\FindClientById\FindClientByIdHandler;
use App\Client\Application\Query\FindClientById\FindClientByIdQuery;
use App\Client\Application\Query\Response\ClientResponse;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use PHPUnit\Framework\TestCase;

final class FindClientByIdHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const VALID_RUC = '1712345675001';

    public function testReturnsClientResponse(): void
    {
        $client = Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject(self::VALID_RUC),
            'Test Address',
            ClientStatus::ACTIVE,
        );

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $handler = new FindClientByIdHandler($clientRepo);
        $response = ($handler)(new FindClientByIdQuery(clientId: self::CLIENT_ID));

        self::assertInstanceOf(ClientResponse::class, $response);
        self::assertSame(self::CLIENT_ID, $response->id);
        self::assertSame('Test Client', $response->name);
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);

        $handler = new FindClientByIdHandler($clientRepo);

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Cliente con ID "' . self::CLIENT_ID . '" no encontrado.');

        ($handler)(new FindClientByIdQuery(clientId: self::CLIENT_ID));
    }
}
