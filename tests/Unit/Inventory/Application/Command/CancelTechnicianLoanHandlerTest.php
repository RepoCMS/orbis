<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CancelTechnicianLoan\CancelTechnicianLoanCommand;
use App\Inventory\Application\Command\CancelTechnicianLoan\CancelTechnicianLoanHandler;
use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Events\TechnicianLoanCancelled;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class CancelTechnicianLoanHandlerTest extends TestCase
{
    private const LOAN_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000002';
    private const FROM_TECHNICIAN_ID = '01HZ0000000000000000000003';
    private const TO_TECHNICIAN_ID = '01HZ0000000000000000000004';
    private const CANCELLED_BY = '01HZ0000000000000000000005';

    private function makePendingLoan(): TechnicianLoan
    {
        return TechnicianLoan::request(
            id:                new TechnicianLoanIdValueObject(self::LOAN_ID),
            serializedStockId: self::SERIALIZED_STOCK_ID,
            fromTechnicianId:  self::FROM_TECHNICIAN_ID,
            toTechnicianId:    self::TO_TECHNICIAN_ID,
        );
    }

    public function testCancelsLoanSuccessfully(): void
    {
        $loan = $this->makePendingLoan();

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn($loan);
        $repository->expects($this->once())->method('save')->with($loan);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TechnicianLoanCancelled::class));

        $handler = new CancelTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        ($handler)(new CancelTechnicianLoanCommand(
            loanId:      self::LOAN_ID,
            cancelledBy: self::CANCELLED_BY,
        ));

        self::assertTrue($loan->isCancelled());
        self::assertSame(self::CANCELLED_BY, $loan->cancelledBy());
    }

    public function testCancelsLoanWithoutCancelledBy(): void
    {
        $loan = $this->makePendingLoan();

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn($loan);
        $repository->expects($this->once())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $handler = new CancelTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        ($handler)(new CancelTechnicianLoanCommand(
            loanId: self::LOAN_ID,
        ));

        self::assertTrue($loan->isCancelled());
        self::assertNull($loan->cancelledBy());
    }

    public function testThrowsWhenLoanNotFound(): void
    {
        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new CancelTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('El préstamo técnico no existe.');

        ($handler)(new CancelTechnicianLoanCommand(
            loanId:      self::LOAN_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }

    public function testThrowsWhenLoanNotPending(): void
    {
        $loan = $this->makePendingLoan();
        $loan->approve(self::CANCELLED_BY);

        $repository = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repository->method('findById')->willReturn($loan);
        $repository->expects($this->never())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new CancelTechnicianLoanHandler(
            technicianLoanRepository: $repository,
            publisher: $publisher,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Solo se pueden cancelar préstamos en estado PENDING_LOAN.');

        ($handler)(new CancelTechnicianLoanCommand(
            loanId:      self::LOAN_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }
}
