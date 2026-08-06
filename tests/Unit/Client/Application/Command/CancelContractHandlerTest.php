<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\CancelContract\CancelContractCommand;
use App\Client\Application\Command\CancelContract\CancelContractHandler;
use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Enums\ContractStatus;
use App\Client\Domain\Events\ContractCancelled;
use App\Client\Domain\Exceptions\ContractStatusException;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CancelContractHandlerTest extends TestCase
{
    private const CONTRACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testCancelsContractSuccessfully(): void
    {
        $contract = Contract::create(
            id: new ContractIdValueObject(self::CONTRACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            startDate: new DateTimeImmutable('2026-01-01'),
        );

        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($contract);
        $repo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ContractCancelled::class));

        $handler = new CancelContractHandler($repo, $eventPublisher);
        ($handler)(new CancelContractCommand(contractId: self::CONTRACT_ID));

        self::assertSame(ContractStatus::CANCELLED, $contract->status());
    }

    public function testThrowsWhenContractNotFound(): void
    {
        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new CancelContractHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(ContractNotFoundException::class);
        ($handler)(new CancelContractCommand(contractId: self::CONTRACT_ID));
    }

    public function testThrowsWhenNotActive(): void
    {
        $contract = Contract::create(
            id: new ContractIdValueObject(self::CONTRACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            startDate: new DateTimeImmutable('2026-01-01'),
            status: ContractStatus::EXPIRED,
        );

        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($contract);

        $handler = new CancelContractHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(ContractStatusException::class);
        $this->expectExceptionMessage('Solo se puede cancelar un contrato activo.');

        ($handler)(new CancelContractCommand(contractId: self::CONTRACT_ID));
    }
}
