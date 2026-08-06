<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateContract\UpdateContractCommand;
use App\Client\Application\Command\UpdateContract\UpdateContractHandler;
use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Events\ContractUpdated;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateContractHandlerTest extends TestCase
{
    private const CONTRACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testUpdatesContractSuccessfully(): void
    {
        $contract = Contract::create(
            id: new ContractIdValueObject(self::CONTRACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2027-01-01'),
            terms: 'Original terms',
            scope: 'Original scope',
        );

        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($contract);
        $repo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ContractUpdated::class));

        $handler = new UpdateContractHandler($repo, $eventPublisher);
        ($handler)(new UpdateContractCommand(
            contractId: self::CONTRACT_ID,
            terms: 'Updated terms',
            scope: 'Updated scope',
            endDate: '2028-01-01',
        ));
    }

    public function testThrowsWhenContractNotFound(): void
    {
        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateContractHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(ContractNotFoundException::class);
        ($handler)(new UpdateContractCommand(
            contractId: self::CONTRACT_ID,
        ));
    }
}
