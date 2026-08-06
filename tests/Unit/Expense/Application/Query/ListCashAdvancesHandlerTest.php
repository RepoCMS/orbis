<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesHandler;
use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesQuery;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\SettlementDirection;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListCashAdvancesHandlerTest extends TestCase
{
    private static function ulid(): string
    {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $ulid = '';
        for ($i = 0; $i < 26; $i++) {
            $ulid .= $chars[random_int(0, 31)];
        }

        return $ulid;
    }
    public function testReturnsAllWhenNoFilters(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findAll')->willReturn([
            $this->createAdvance('EMP001'),
            $this->createAdvance('EMP002'),
        ]);

        $handler = new ListCashAdvancesHandler($repo);
        $result = ($handler)(new ListCashAdvancesQuery());

        self::assertCount(2, $result);
    }

    public function testFiltersByEmployeeId(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findAll')->willReturn([
            $this->createAdvance('EMP001'),
            $this->createAdvance('EMP002'),
        ]);

        $handler = new ListCashAdvancesHandler($repo);
        $result = ($handler)(new ListCashAdvancesQuery(employeeId: 'EMP001'));

        self::assertCount(1, $result);
    }

    public function testFiltersByStatus(): void
    {
        $settled = $this->createAdvance('EMP001');
        $settled->settle('ADMIN001', SettlementDirection::TECH_TO_COMPANY);

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findAll')->willReturn([
            $this->createAdvance('EMP001'),
            $settled,
        ]);

        $handler = new ListCashAdvancesHandler($repo);
        $result = ($handler)(new ListCashAdvancesQuery(status: 'settled'));

        self::assertCount(1, $result);
    }

    public function testFiltersByDaysOverdue(): void
    {
        $old = $this->createAdvance('EMP001', -10);
        $recent = $this->createAdvance('EMP001', -1);

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$old, $recent]);

        $handler = new ListCashAdvancesHandler($repo);
        $result = ($handler)(new ListCashAdvancesQuery(daysOverdue: 5));

        self::assertCount(1, $result);
    }

    public function testReturnsEmptyWhenNoMatch(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$this->createAdvance('EMP001')]);

        $handler = new ListCashAdvancesHandler($repo);
        $result = ($handler)(new ListCashAdvancesQuery(employeeId: 'NONEXISTENT'));

        self::assertCount(0, $result);
    }

    private function createAdvance(string $employeeId, int $daysAgo = 0): CashAdvance
    {
        return CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ulid()),
            advanceNumber: 'AV-2026-001',
            employeeId: $employeeId,
            givenBy: 'ADMIN001',
            amount: 100.0,
            givenAt: (new \DateTimeImmutable())->modify("{$daysAgo} days"),
        );
    }
}
