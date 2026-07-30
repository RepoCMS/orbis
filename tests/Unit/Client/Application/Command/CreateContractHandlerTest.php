<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\CreateContract\CreateContractCommand;
use App\Client\Application\Command\CreateContract\CreateContractHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Enums\ContractStatus;
use App\Client\Domain\Events\ContractCreated;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class CreateContractHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testCreatesContractSuccessfully(): void
    {
        $client = Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
            'Address',
            ClientStatus::ACTIVE,
        );

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $contractRepo = $this->createMock(ContractRepositoryInterface::class);
        $contractRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(ContractCreated::class));

        $handler = new CreateContractHandler(
            $clientRepo,
            $contractRepo,
            $idGenerator,
            $publisher,
            $idempotency,
        );

        ($handler)(new CreateContractCommand(
            clientId: self::CLIENT_ID,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            terms: 'Standard terms',
            scope: 'Full coverage',
        ));

        self::assertTrue(true);
    }

    public function testCreatesContractWithoutOptionalFields(): void
    {
        $client = Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
        );

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $contractRepo = $this->createMock(ContractRepositoryInterface::class);
        $contractRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreateContractHandler(
            $clientRepo,
            $contractRepo,
            $idGenerator,
            $this->createMock(DomainEventPublisherInterface::class),
            $idempotency,
        );

        ($handler)(new CreateContractCommand(
            clientId: self::CLIENT_ID,
            startDate: '2026-01-01',
        ));

        self::assertTrue(true);
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreateContractHandler(
            $clientRepo,
            $this->createMock(ContractRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $idempotency,
        );

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage(self::CLIENT_ID);

        ($handler)(new CreateContractCommand(
            clientId: self::CLIENT_ID,
            startDate: '2026-01-01',
        ));
    }

    public function testThrowsWhenIdempotencyCheckFails(): void
    {
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(false);

        $handler = new CreateContractHandler(
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(ContractRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $idempotency,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Esta operación ya fue procesada.');

        ($handler)(new CreateContractCommand(
            clientId: self::CLIENT_ID,
            startDate: '2026-01-01',
        ));
    }
}
