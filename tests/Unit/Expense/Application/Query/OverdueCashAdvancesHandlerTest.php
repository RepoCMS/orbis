<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\OverdueCashAdvances\OverdueCashAdvancesHandler;
use App\Expense\Application\Query\OverdueCashAdvances\OverdueCashAdvancesQuery;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class OverdueCashAdvancesHandlerTest extends TestCase
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
    public function testReturnsOverdueAdvances(): void
    {
        $overdue = $this->createAdvance(-10);
        $recent = $this->createAdvance(-1);

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findByStatus')->with(CashAdvanceStatus::OUTSTANDING)->willReturn([$overdue, $recent]);

        $handler = new OverdueCashAdvancesHandler($repo);
        $result = ($handler)(new OverdueCashAdvancesQuery(cutoffDays: 3));

        self::assertCount(1, $result);
    }

    public function testReturnsEmptyWhenNoOverdue(): void
    {
        $recent = $this->createAdvance(-1);

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findByStatus')->willReturn([$recent]);

        $handler = new OverdueCashAdvancesHandler($repo);
        $result = ($handler)(new OverdueCashAdvancesQuery(cutoffDays: 5));

        self::assertCount(0, $result);
    }

    public function testUsesDefaultCutoffOfThreeDays(): void
    {
        $overdue = $this->createAdvance(-4);
        $recent = $this->createAdvance(-2);

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findByStatus')->willReturn([$overdue, $recent]);

        $handler = new OverdueCashAdvancesHandler($repo);
        $result = ($handler)(new OverdueCashAdvancesQuery());

        self::assertCount(1, $result);
    }

    private function createAdvance(int $daysAgo): CashAdvance
    {
        return CashAdvance::create(
            id: new CashAdvanceIdValueObject(self::ulid()),
            advanceNumber: 'AV-2026-001',
            employeeId: 'EMP001',
            givenBy: 'ADMIN001',
            amount: 100.0,
            givenAt: (new \DateTimeImmutable())->modify("{$daysAgo} days"),
        );
    }
}
