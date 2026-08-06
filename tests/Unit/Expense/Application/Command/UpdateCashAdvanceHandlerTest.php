<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\UpdateCashAdvance\UpdateCashAdvanceCommand;
use App\Expense\Application\Command\UpdateCashAdvance\UpdateCashAdvanceHandler;
use App\Expense\Application\Exception\CashAdvanceNotFoundException;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateCashAdvanceHandlerTest extends TestCase
{
    private const ADVANCE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testUpdatesAmountSuccessfully(): void
    {
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USR001',
            amount: 200.00,
            givenAt: new DateTimeImmutable('2026-01-15'),
        );
        $advance->pullDomainEvents();

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn($advance);
        $repo->expects($this->once())->method('save');

        $handler = new UpdateCashAdvanceHandler(
            $repo,
            $this->createMock(\App\Shared\Domain\Event\DomainEventPublisherInterface::class),
        );

        ($handler)(new UpdateCashAdvanceCommand(
            cashAdvanceId: self::ADVANCE_ID,
            newAmount: 150.00,
        ));

        self::assertSame(150.00, $advance->amount());
    }

    public function testThrowsWhenAdvanceNotFound(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateCashAdvanceHandler(
            $repo,
            $this->createMock(\App\Shared\Domain\Event\DomainEventPublisherInterface::class),
        );

        $this->expectException(CashAdvanceNotFoundException::class);
        ($handler)(new UpdateCashAdvanceCommand(
            cashAdvanceId: self::ADVANCE_ID,
            newAmount: 150.00,
        ));
    }
}
