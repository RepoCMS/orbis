<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Query;

use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailHandler;
use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailQuery;
use App\Expense\Application\Query\Response\OperatingExpenseResponse;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use PHPUnit\Framework\TestCase;

final class GetOperatingExpenseDetailHandlerTest extends TestCase
{
    public function testReturnsResponseWhenFound(): void
    {
        $id = new OperatingExpenseIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $expense = OperatingExpense::create(
            id: $id,
            sequence: 1,
            employeeId: 'EMP001',
            reason: 'Gastos de viaje',
            startDate: new \DateTimeImmutable('2026-01-15'),
            endDate: new \DateTimeImmutable('2026-01-20'),
        );

        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn($expense);

        $handler = new GetOperatingExpenseDetailHandler($repo);
        $result = ($handler)(new GetOperatingExpenseDetailQuery('01ARZ3NDEKTSV4RRFFQ69G5FAV'));

        self::assertInstanceOf(OperatingExpenseResponse::class, $result);
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $result->id);
        self::assertSame('Gastos de viaje', $result->reason);
    }

    public function testReturnsNullWhenNotFound(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new GetOperatingExpenseDetailHandler($repo);
        $result = ($handler)(new GetOperatingExpenseDetailQuery('01ARZ3NDEKTSV4RRFFQ69G5FAV'));

        self::assertNull($result);
    }
}
