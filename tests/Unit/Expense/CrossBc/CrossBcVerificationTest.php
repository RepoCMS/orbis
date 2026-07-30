<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\CrossBc;

use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\TicketResolverInterface;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Infrastructure\Messenger\Handler\EmployeeDeactivatedHandler;
use App\Expense\Infrastructure\Service\CrossBcEmployeeResolver;
use App\Expense\Infrastructure\Service\CrossBcTicketResolver;
use App\Expense\Infrastructure\Service\CrossBcUserResolver;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use PHPUnit\Framework\TestCase;

/**
 * Cross-BC Verification Tests.
 *
 * Verifies that Expense BC correctly implements cross-boundary contracts:
 * - Resolver interfaces for Organization, Support, and Identity BCs
 * - Event subscriber for EmployeeDeactivated from Organization BC
 */
final class CrossBcVerificationTest extends TestCase
{
    // ─── T7.1 EmployeeResolverInterface ─────────────────────────────────────

    public function testEmployeeResolverInterfaceExists(): void
    {
        self::assertTrue(interface_exists(EmployeeResolverInterface::class));
    }

    public function testEmployeeResolverHasConcreteImplementation(): void
    {
        self::assertTrue(class_exists(CrossBcEmployeeResolver::class));
        self::assertInstanceOf(
            EmployeeResolverInterface::class,
            new CrossBcEmployeeResolver($this->createMock(\Doctrine\DBAL\Connection::class))
        );
    }

    // ─── T7.2 TicketResolverInterface ──────────────────────────────────────

    public function testTicketResolverInterfaceExists(): void
    {
        self::assertTrue(interface_exists(TicketResolverInterface::class));
    }

    public function testTicketResolverHasConcreteImplementation(): void
    {
        self::assertTrue(class_exists(CrossBcTicketResolver::class));
        self::assertInstanceOf(
            TicketResolverInterface::class,
            new CrossBcTicketResolver($this->createMock(\Doctrine\DBAL\Connection::class))
        );
    }

    // ─── T7.3 UserResolverInterface ─────────────────────────────────────────

    public function testUserResolverInterfaceExists(): void
    {
        self::assertTrue(interface_exists(UserResolverInterface::class));
    }

    public function testUserResolverHasConcreteImplementation(): void
    {
        self::assertTrue(class_exists(CrossBcUserResolver::class));
        self::assertInstanceOf(
            UserResolverInterface::class,
            new CrossBcUserResolver($this->createMock(\Doctrine\DBAL\Connection::class))
        );
    }

    // ─── T7.4 EmployeeDeactivated Subscriber ────────────────────────────────

    public function testEmployeeDeactivatedHandlerExists(): void
    {
        self::assertTrue(class_exists(EmployeeDeactivatedHandler::class));
    }

    public function testEmployeeDeactivatedHandlerProcessesCorrectEvent(): void
    {
        $cashAdvanceRepo = $this->createMock(\App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface::class);
        $handler = new EmployeeDeactivatedHandler($cashAdvanceRepo);

        // Should be callable with DomainEventMessage wrapping EmployeeDeactivated
        $event = new EmployeeDeactivated(employeeId: 'EMP-001', userId: 'USER-001');
        $message = DomainEventMessage::fromEvent($event);

        // The handler should attempt to find advances for the employee
        $cashAdvanceRepo->expects($this->once())
            ->method('findByEmployeeId')
            ->with('EMP-001')
            ->willReturn([]);

        $handler($message);
    }

    public function testEmployeeDeactivatedHandlerIgnoresOtherEvents(): void
    {
        $cashAdvanceRepo = $this->createMock(\App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface::class);
        $handler = new EmployeeDeactivatedHandler($cashAdvanceRepo);

        // Should ignore non-EmployeeDeactivated events
        $cashAdvanceRepo->expects($this->never())->method('findByEmployeeId');

        $otherEvent = new \App\Expense\Domain\Events\CashAdvanceCreated(
            cashAdvanceId: '01HZ0000000000000000000001',
            employeeId: 'EMP-001',
            amount: 500.0,
            advanceNumber: 'ADV-2026-0001',
        );
        $message = DomainEventMessage::fromEvent($otherEvent);

        $handler($message);
    }

    // ─── T7.5 Domain Events for Notification BC ────────────────────────────

    public function testExpenseSubmittedEventExists(): void
    {
        self::assertTrue(class_exists(\App\Expense\Domain\Events\ExpenseSubmitted::class));
    }

    public function testExpenseApprovedEventExists(): void
    {
        self::assertTrue(class_exists(\App\Expense\Domain\Events\ExpenseApproved::class));
    }

    public function testCashAdvanceCreatedEventExists(): void
    {
        self::assertTrue(class_exists(\App\Expense\Domain\Events\CashAdvanceCreated::class));
    }

    public function testCashAdvanceSettledEventExists(): void
    {
        self::assertTrue(class_exists(\App\Expense\Domain\Events\CashAdvanceSettled::class));
    }

    public function testCashAdvancePendingReviewEventExists(): void
    {
        self::assertTrue(class_exists(\App\Expense\Domain\Events\CashAdvancePendingReview::class));
    }

    public function testExpenseItemRejectedEventExists(): void
    {
        self::assertTrue(class_exists(\App\Expense\Domain\Events\ExpenseItemRejected::class));
    }
}
