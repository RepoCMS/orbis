<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Application\Query\FindContractById\FindContractByIdHandler;
use App\Client\Application\Query\FindContractById\FindContractByIdQuery;
use App\Client\Application\Query\Response\ContractResponse;
use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindContractByIdHandlerTest extends TestCase
{
    private const CONTRACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testReturnsContractResponse(): void
    {
        $contract = Contract::create(
            id: new ContractIdValueObject(self::CONTRACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            startDate: new DateTimeImmutable('2026-01-01'),
        );

        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($contract);

        $handler = new FindContractByIdHandler($repo);
        $response = ($handler)(new FindContractByIdQuery(contractId: self::CONTRACT_ID));

        self::assertInstanceOf(ContractResponse::class, $response);
        self::assertSame(self::CONTRACT_ID, $response->id);
    }

    public function testThrowsWhenContractNotFound(): void
    {
        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindContractByIdHandler($repo);

        $this->expectException(ContractNotFoundException::class);
        ($handler)(new FindContractByIdQuery(contractId: self::CONTRACT_ID));
    }
}
