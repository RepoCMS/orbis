<?php

declare(strict_types=1);

namespace Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\ListClients\ListClientsHandler;
use App\Client\Application\Query\ListClients\ListClientsQuery;
use App\Client\Application\Query\Response\ClientResponse;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use PHPUnit\Framework\TestCase;

final class ListClientsHandlerTest extends TestCase
{
    public function testInvokeReturnsClientResponses(): void
    {
        $mockRepository = $this->createMock(ClientRepositoryInterface::class);

        $client = Client::createForTest(
            new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
            'Test Address',
            ClientStatus::ACTIVE
        );

        $mockRepository->method('search')->willReturn([$client]);

        $handler = new ListClientsHandler($mockRepository);

        $query = new ListClientsQuery(
            name: 'Test',
            status: ClientStatus::ACTIVE,
            page: 1,
            limit: 10
        );

        $result = $handler($query);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(ClientResponse::class, $result[0]);
        $this->assertEquals('Test Client', $result[0]->name);
    }
}
