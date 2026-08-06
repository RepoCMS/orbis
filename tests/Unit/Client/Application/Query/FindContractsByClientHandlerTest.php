<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\FindContractsByClient\FindContractsByClientHandler;
use App\Client\Application\Query\FindContractsByClient\FindContractsByClientQuery;
use App\Client\Application\Query\Response\ContractResponse;
use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindContractsByClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testReturnsContractResponses(): void
    {
        $contract = Contract::create(
            id: new ContractIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            startDate: new DateTimeImmutable('2026-01-01'),
        );

        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findByClientId')->willReturn([$contract]);

        $handler = new FindContractsByClientHandler($repo);
        $result = ($handler)(new FindContractsByClientQuery(clientId: self::CLIENT_ID));

        self::assertCount(1, $result);
        self::assertInstanceOf(ContractResponse::class, $result[0]);
    }

    public function testReturnsEmptyArray(): void
    {
        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findByClientId')->willReturn([]);

        $handler = new FindContractsByClientHandler($repo);
        $result = ($handler)(new FindContractsByClientQuery(clientId: self::CLIENT_ID));

        self::assertCount(0, $result);
    }
}
