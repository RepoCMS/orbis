<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\CheckOverdueCashAdvances\CheckOverdueCashAdvancesCommand;
use App\Expense\Application\Command\CheckOverdueCashAdvances\CheckOverdueCashAdvancesHandler;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Events\CashAdvancePaymentPending;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CheckOverdueCashAdvancesHandlerTest extends TestCase
{
    private const ADVANCE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testPublishesEventForOverdueAdvances(): void
    {
        $oldAdvance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USR001',
            amount: 200.00,
            givenAt: new DateTimeImmutable('-5 days'),
        );

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findByStatus')
            ->with(CashAdvanceStatus::OUTSTANDING)
            ->willReturn([$oldAdvance]);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(CashAdvancePaymentPending::class));

        $handler = new CheckOverdueCashAdvancesHandler($repo, $publisher);
        ($handler)(new CheckOverdueCashAdvancesCommand());
    }

    public function testDoesNothingWhenNoOverdueAdvances(): void
    {
        $recentAdvance = CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ADVANCE_ID),
            advanceNumber: 'CA-2026-0001',
            employeeId: 'EMP001',
            givenBy: 'USR001',
            amount: 200.00,
            givenAt: new DateTimeImmutable('-1 day'),
        );

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findByStatus')
            ->with(CashAdvanceStatus::OUTSTANDING)
            ->willReturn([$recentAdvance]);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->never())->method('publish');

        $handler = new CheckOverdueCashAdvancesHandler($repo, $publisher);
        ($handler)(new CheckOverdueCashAdvancesCommand());
    }
}
