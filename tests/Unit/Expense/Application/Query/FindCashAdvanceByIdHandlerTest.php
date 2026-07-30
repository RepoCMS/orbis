<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\FindCashAdvanceById\FindCashAdvanceByIdHandler;
use App\Expense\Application\Query\FindCashAdvanceById\FindCashAdvanceByIdQuery;
use App\Expense\Application\Query\Response\CashAdvanceResponse;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindCashAdvanceByIdHandlerTest extends TestCase
{
    public function testReturnsResponseWhenFound(): void
    {
        $id = new CashAdvanceIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $advance = CashAdvance::create(
            id: $id,
            advanceNumber: 'AV-2026-001',
            employeeId: 'EMP001',
            givenBy: 'ADMIN001',
            amount: 200.0,
            givenAt: new \DateTimeImmutable('2026-06-01'),
        );

        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn($advance);

        $handler = new FindCashAdvanceByIdHandler($repo);
        $result = ($handler)(new FindCashAdvanceByIdQuery('01ARZ3NDEKTSV4RRFFQ69G5FAV'));

        self::assertInstanceOf(CashAdvanceResponse::class, $result);
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $result->id);
        self::assertSame('AV-2026-001', $result->advanceNumber);
    }

    public function testReturnsNullWhenNotFound(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindCashAdvanceByIdHandler($repo);
        $result = ($handler)(new FindCashAdvanceByIdQuery('01ARZ3NDEKTSV4RRFFQ69G5FAV'));

        self::assertNull($result);
    }
}
