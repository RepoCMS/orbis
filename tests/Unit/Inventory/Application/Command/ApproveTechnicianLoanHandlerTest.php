<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\ApproveTechnicianLoan\ApproveTechnicianLoanCommand;
use App\Inventory\Application\Command\ApproveTechnicianLoan\ApproveTechnicianLoanHandler;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Events\TechnicianLoanApproved;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ApproveTechnicianLoanHandlerTest extends TestCase
{
    private const LOAN_ID = '01HZ0000000000000000000001';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000002';
    private const FROM_TECHNICIAN_ID = '01HZ0000000000000000000003';
    private const TO_TECHNICIAN_ID = '01HZ0000000000000000000004';
    private const TICKET_ID = '01HZ0000000000000000000005';
    private const MOVEMENT_ID = '01HZ0000000000000000000010';
    private const PRODUCT_ID = '01HZ0000000000000000000020';
    private const APPROVED_BY = '01HZ0000000000000000000030';
    private const LOCATION_ID = '01HZ0000000000000000000040';

    private function makePendingLoan(): TechnicianLoan
    {
        return TechnicianLoan::request(
            id:                new TechnicianLoanIdValueObject(self::LOAN_ID),
            serializedStockId: self::SERIALIZED_STOCK_ID,
            fromTechnicianId:  self::FROM_TECHNICIAN_ID,
            toTechnicianId:    self::TO_TECHNICIAN_ID,
            ticketId:          self::TICKET_ID,
        );
    }

    private function makeSerializedStock(): SerializedStock
    {
        return SerializedStock::register(
            id:           new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            productId:    new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject('SN-LOAN-001'),
            locationId:   new LocationIdValueObject(self::LOCATION_ID),
        );
    }

    public function testApprovesLoanAndCreatesMovement(): void
    {
        $loan = $this->makePendingLoan();
        $serializedStock = $this->makeSerializedStock();

        $loanRepo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $loanRepo->method('findById')->willReturn($loan);
        $loanRepo->expects($this->once())->method('save')->with($loan);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($serializedStock);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::MOVEMENT_ID);

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TechnicianLoanApproved::class));

        $handler = new ApproveTechnicianLoanHandler(
            technicianLoanRepository: $loanRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            publisher: $publisher,
        );

        ($handler)(new ApproveTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            approvedBy: self::APPROVED_BY,
        ));

        self::assertTrue($loan->isApproved());
        self::assertSame(self::APPROVED_BY, $loan->approvedBy());
        self::assertNotNull($loan->approvedAt());

        self::assertCount(1, $savedMovements);
        $movement = $savedMovements[0];
        self::assertSame(self::MOVEMENT_ID, (string) $movement->id());
        self::assertSame(self::PRODUCT_ID, (string) $movement->productId());
        self::assertSame(MovementType::LOAN, $movement->type());
        self::assertSame(MovementStatus::COMPLETED, $movement->status());
        self::assertSame(self::SERIALIZED_STOCK_ID, (string) $movement->serializedStockId());
        self::assertSame(1, $movement->quantity());
        self::assertNull($movement->sourceLocationId());
        self::assertNull($movement->destinationLocationId());
    }

    public function testApprovesLoanWithExplicitMovementId(): void
    {
        $loan = $this->makePendingLoan();
        $serializedStock = $this->makeSerializedStock();
        $explicitMovementId = '01HZ0000000000000000000099';

        $loanRepo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $loanRepo->method('findById')->willReturn($loan);
        $loanRepo->expects($this->once())->method('save');

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn($serializedStock);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->never())->method('generate');

        $savedMovements = [];
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Movement $m) use (&$savedMovements): void {
                $savedMovements[] = $m;
            });

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $handler = new ApproveTechnicianLoanHandler(
            technicianLoanRepository: $loanRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            publisher: $publisher,
        );

        ($handler)(new ApproveTechnicianLoanCommand(
            loanId:      self::LOAN_ID,
            approvedBy:  self::APPROVED_BY,
            movementId:  $explicitMovementId,
        ));

        self::assertCount(1, $savedMovements);
        self::assertSame($explicitMovementId, (string) $savedMovements[0]->id());
    }

    public function testThrowsWhenLoanNotFound(): void
    {
        $loanRepo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $loanRepo->method('findById')->willReturn(null);
        $loanRepo->expects($this->never())->method('save');

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->expects($this->never())->method('save');

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new ApproveTechnicianLoanHandler(
            technicianLoanRepository: $loanRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            publisher: $publisher,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El préstamo técnico no existe.');

        ($handler)(new ApproveTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            approvedBy: self::APPROVED_BY,
        ));
    }

    public function testThrowsWhenLoanNotPending(): void
    {
        $loan = $this->makePendingLoan();
        $loan->approve(self::APPROVED_BY);

        $loanRepo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $loanRepo->method('findById')->willReturn($loan);
        $loanRepo->expects($this->never())->method('save');

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new ApproveTechnicianLoanHandler(
            technicianLoanRepository: $loanRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            publisher: $publisher,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden aprobar préstamos en estado PENDING_LOAN.');

        ($handler)(new ApproveTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            approvedBy: self::APPROVED_BY,
        ));
    }

    public function testThrowsWhenSerializedStockNotFound(): void
    {
        $loan = $this->makePendingLoan();

        $loanRepo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $loanRepo->method('findById')->willReturn($loan);

        $serializedRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $serializedRepo->method('findById')->willReturn(null);

        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new ApproveTechnicianLoanHandler(
            technicianLoanRepository: $loanRepo,
            serializedStockRepository: $serializedRepo,
            movementRepository: $movementRepo,
            idGenerator: $idGenerator,
            publisher: $publisher,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El stock serializado especificado no existe.');

        ($handler)(new ApproveTechnicianLoanCommand(
            loanId:     self::LOAN_ID,
            approvedBy: self::APPROVED_BY,
        ));
    }
}
