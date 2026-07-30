<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CreateTechnicianLoan\CreateTechnicianLoanCommand;
use App\Inventory\Application\Command\CreateTechnicianLoan\CreateTechnicianLoanHandler;
use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Events\TechnicianLoanRequested;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class CreateTechnicianLoanHandlerTest extends TestCase
{
    private const LOAN_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000002';
    private const FROM_TECHNICIAN_ID = '01HZ0000000000000000000003';
    private const TO_TECHNICIAN_ID = '01HZ0000000000000000000004';
    private const TICKET_ID = '01HZ0000000000000000000005';
    private const NOTES = 'Préstamo entre técnicos';

    public function testCreatesTechnicianLoanSuccessfully(): void
    {
        $loanId = new TechnicianLoanIdValueObject(self::LOAN_ID);

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (TechnicianLoan $loan) use ($loanId): bool {
                return (string) $loan->id() === (string) $loanId
                    && $loan->serializedStockId() === self::SERIALIZED_STOCK_ID
                    && $loan->fromTechnicianId() === self::FROM_TECHNICIAN_ID
                    && $loan->toTechnicianId() === self::TO_TECHNICIAN_ID
                    && $loan->ticketId() === self::TICKET_ID
                    && $loan->notes() === self::NOTES
                    && $loan->isPending();
            }));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::LOAN_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TechnicianLoanRequested::class));

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');
        $txManager->expects($this->never())->method('rollback');

        $handler = new CreateTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            idGenerator: $idGenerator,
            publisher: $publisher,
            transactionManager: $txManager,
        );

        ($handler)(new CreateTechnicianLoanCommand(
            serializedStockId: self::SERIALIZED_STOCK_ID,
            fromTechnicianId:  self::FROM_TECHNICIAN_ID,
            toTechnicianId:    self::TO_TECHNICIAN_ID,
            ticketId:          self::TICKET_ID,
            notes:             self::NOTES,
        ));
    }

    public function testCreatesLoanWithoutOptionalFields(): void
    {
        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::LOAN_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->once())->method('commit');
        $txManager->expects($this->never())->method('rollback');

        $handler = new CreateTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            idGenerator: $idGenerator,
            publisher: $publisher,
            transactionManager: $txManager,
        );

        ($handler)(new CreateTechnicianLoanCommand(
            serializedStockId: self::SERIALIZED_STOCK_ID,
            fromTechnicianId:  self::FROM_TECHNICIAN_ID,
            toTechnicianId:    self::TO_TECHNICIAN_ID,
        ));
    }

    public function testRollsBackOnException(): void
    {
        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('save')->willThrowException(new \RuntimeException('DB error'));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::LOAN_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $txManager = $this->createMock(TransactionManagerInterface::class);
        $txManager->expects($this->once())->method('begin');
        $txManager->expects($this->never())->method('commit');
        $txManager->expects($this->once())->method('rollback');

        $handler = new CreateTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            idGenerator: $idGenerator,
            publisher: $publisher,
            transactionManager: $txManager,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Error al procesar la solicitud.');

        ($handler)(new CreateTechnicianLoanCommand(
            serializedStockId: self::SERIALIZED_STOCK_ID,
            fromTechnicianId:  self::FROM_TECHNICIAN_ID,
            toTechnicianId:    self::TO_TECHNICIAN_ID,
        ));
    }
}
