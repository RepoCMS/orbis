<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceCommand;
use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceHandler;
use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateCashAdvanceHandlerTest extends TestCase
{
    private const ADVANCE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testCreatesCashAdvanceSuccessfully(): void
    {
        $repo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $repo->method('nextAdvanceNumber')->willReturn('CA-2026-0001');
        $repo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::ADVANCE_ID);

        $employeeResolver = $this->createMock(EmployeeResolverInterface::class);
        $userResolver = $this->createMock(UserResolverInterface::class);

        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $handler = new CreateCashAdvanceHandler(
            $repo, $idGenerator, $employeeResolver, $userResolver,
            $this->createMock(DomainEventPublisherInterface::class),
            $idempotency,
        );

        $result = ($handler)(new CreateCashAdvanceCommand(
            employeeId: 'EMP001',
            givenBy: 'USR001',
            amount: 200.00,
            givenAt: '2026-01-15',
        ));

        self::assertSame(self::ADVANCE_ID, $result);
    }

    public function testThrowsOnInvalidAmount(): void
    {
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::ADVANCE_ID);

        $this->expectException(\App\Expense\Domain\Exceptions\InvalidAmountException::class);

        $handler = new CreateCashAdvanceHandler(
            $this->createMock(CashAdvanceRepositoryInterface::class),
            $idGenerator,
            $this->createMock(EmployeeResolverInterface::class),
            $this->createMock(UserResolverInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $idempotency,
        );

        ($handler)(new CreateCashAdvanceCommand(
            employeeId: 'EMP001',
            givenBy: 'USR001',
            amount: -50,
            givenAt: '2026-01-15',
        ));
    }
}
