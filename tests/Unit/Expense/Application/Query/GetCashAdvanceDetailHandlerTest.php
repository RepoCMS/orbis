<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\GetCashAdvanceDetail\GetCashAdvanceDetailHandler;
use App\Expense\Application\Query\GetCashAdvanceDetail\GetCashAdvanceDetailQuery;
use App\Expense\Application\Query\Response\CashAdvanceDetailResponse;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class GetCashAdvanceDetailHandlerTest extends TestCase
{
    public function testReturnsDetailResponseWhenFound(): void
    {
        $advance = CashAdvance::create(
            id: new CashAdvanceIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            advanceNumber: 'AV-2026-001',
            employeeId: 'EMP001',
            givenBy: 'ADMIN001',
            amount: 200.0,
            givenAt: new \DateTimeImmutable('2026-06-01'),
        );

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn($advance);

        $handler = new GetCashAdvanceDetailHandler($repo);
        $result = ($handler)(new GetCashAdvanceDetailQuery('01ARZ3NDEKTSV4RRFFQ69G5FAV'));

        self::assertInstanceOf(CashAdvanceDetailResponse::class, $result);
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $result->id);
        self::assertTrue($result->isOverdue);
    }

    public function testReturnsNullWhenNotFound(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new GetCashAdvanceDetailHandler($repo);
        $result = ($handler)(new GetCashAdvanceDetailQuery('01ARZ3NDEKTSV4RRFFQ69G5FAV'));

        self::assertNull($result);
    }
}
