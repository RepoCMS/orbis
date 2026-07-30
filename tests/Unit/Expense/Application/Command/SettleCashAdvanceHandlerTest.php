<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceCommand;
use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceHandler;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Enums\SettlementDirection;
use App\Expense\Domain\Events\CashAdvanceSettled;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SettleCashAdvanceHandlerTest extends TestCase
{
    private const ADVANCE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testSettlesCashAdvanceSuccessfully(): void
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

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(CashAdvanceSettled::class));

        $handler = new SettleCashAdvanceHandler(
            $repo,
            $this->createMock(UserResolverInterface::class),
            $publisher,
        );

        ($handler)(new SettleCashAdvanceCommand(
            cashAdvanceId: self::ADVANCE_ID,
            settlementConfirmedBy: 'USR002',
            settlementDirection: 'tech_to_company',
        ));

        self::assertSame(CashAdvanceStatus::SETTLED, $advance->status());
    }

    public function testThrowsWhenAdvanceNotFound(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new SettleCashAdvanceHandler(
            $repo,
            $this->createMock(UserResolverInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($handler)(new SettleCashAdvanceCommand(
            cashAdvanceId: self::ADVANCE_ID,
            settlementConfirmedBy: 'USR002',
            settlementDirection: 'tech_to_company',
        ));
    }
}
