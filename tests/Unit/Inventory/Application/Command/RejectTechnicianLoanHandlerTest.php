<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RejectTechnicianLoan\RejectTechnicianLoanCommand;
use App\Inventory\Application\Command\RejectTechnicianLoan\RejectTechnicianLoanHandler;
use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Events\TechnicianLoanRejected;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class RejectTechnicianLoanHandlerTest extends TestCase
{
    private const LOAN_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000002';
    private const FROM_TECHNICIAN_ID = '01HZ0000000000000000000003';
    private const TO_TECHNICIAN_ID = '01HZ0000000000000000000004';
    private const REJECTED_BY = '01HZ0000000000000000000005';
    private const REJECTION_REASON = 'Stock no disponible en inventario';

    private function makePendingLoan(): TechnicianLoan
    {
        return TechnicianLoan::request(
            id:                new TechnicianLoanIdValueObject(self::LOAN_ID),
            serializedStockId: self::SERIALIZED_STOCK_ID,
            fromTechnicianId:  self::FROM_TECHNICIAN_ID,
            toTechnicianId:    self::TO_TECHNICIAN_ID,
        );
    }

    public function testRejectsLoanSuccessfully(): void
    {
        $loan = $this->makePendingLoan();

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn($loan);
        $repository->expects($this->once())->method('save')->with($loan);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TechnicianLoanRejected::class));

        $handler = new RejectTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        ($handler)(new RejectTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            reason:     self::REJECTION_REASON,
            rejectedBy: self::REJECTED_BY,
        ));

        self::assertTrue($loan->isRejected());
        self::assertSame(self::REJECTION_REASON, $loan->rejectionReason());
        self::assertSame(self::REJECTED_BY, $loan->rejectedBy());
        self::assertNotNull($loan->rejectedAt());
    }

    public function testRejectsLoanWithoutRejectedBy(): void
    {
        $loan = $this->makePendingLoan();

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn($loan);
        $repository->expects($this->once())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $handler = new RejectTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        ($handler)(new RejectTechnicianLoanCommand(
            loanId: self::LOAN_ID,
            reason: self::REJECTION_REASON,
        ));

        self::assertTrue($loan->isRejected());
        self::assertSame(self::REJECTION_REASON, $loan->rejectionReason());
        self::assertNull($loan->rejectedBy());
    }

    public function testThrowsWhenLoanNotFound(): void
    {
        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new RejectTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('El préstamo técnico no existe.');

        ($handler)(new RejectTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            reason:     self::REJECTION_REASON,
            rejectedBy: self::REJECTED_BY,
        ));
    }

    public function testThrowsWhenLoanNotPending(): void
    {
        $loan = $this->makePendingLoan();
        $loan->approve(self::REJECTED_BY);

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn($loan);
        $repository->expects($this->never())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new RejectTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Solo se pueden rechazar préstamos en estado PENDING_LOAN.');

        ($handler)(new RejectTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            reason:     self::REJECTION_REASON,
            rejectedBy: self::REJECTED_BY,
        ));
    }
}
