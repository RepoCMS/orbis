<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\SupportDashboardMetrics\SupportDashboardMetricsHandler;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class SupportDashboardMetricsHandlerTest extends TestCase
{
    public function testReturnsEmptyMetricsWhenNoData(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchAllAssociative')->willReturn([]);
        $dbal->method('fetchOne')->willReturn('0');

        $handler = new SupportDashboardMetricsHandler($dbal);
        $metrics = $handler();

        self::assertSame(0, $metrics->totalTickets);
        self::assertSame(0, $metrics->openTickets);
        self::assertSame(0, $metrics->assignedTickets);
        self::assertSame(0, $metrics->inProgressTickets);
        self::assertSame(0, $metrics->pendingClosure);
        self::assertSame(0, $metrics->overdueSla);
        self::assertSame(0, $metrics->completedToday);
        self::assertSame(0, $metrics->completedThisWeek);
        self::assertSame(0.0, $metrics->avgResolutionHours);
        self::assertSame(0, $metrics->slaBreachCount30d);
        self::assertSame(0.0, $metrics->slaBreachRate30d);
        self::assertSame(0, $metrics->reopenedCount);
        self::assertSame(0.0, $metrics->reopenRate);
        self::assertSame(0, $metrics->cancelledCount);
        self::assertSame(0.0, $metrics->cancellationRate);
        self::assertSame(0, $metrics->techniciansAvailable);
        self::assertSame(0, $metrics->techniciansBusy);
        self::assertSame([], $metrics->statusBreakdown);
        self::assertSame([], $metrics->topTechnicians);
        self::assertSame([], $metrics->topClientsByTickets);
        self::assertSame([], $metrics->completionsTrend);
        self::assertSame([], $metrics->recentTickets);
        self::assertSame([], $metrics->avgTimeByTechnician);
    }

    public function testReturnsMetricsWithData(): void
    {
        $dbal = $this->createMock(Connection::class);

        $dbal->expects(self::exactly(7))->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [['status' => 'created', 'cnt' => '5'], ['status' => 'closed', 'cnt' => '10']],
                [['employee_id' => 'emp1', 'cnt' => '10'], ['employee_id' => 'emp2', 'cnt' => '7']],
                [['client_id' => 'cli1', 'cnt' => '15']],
                [['day' => '2026-06-28', 'cnt' => '3']],
                [['id' => '1', 'ticket_number' => 'TK-001', 'client_id' => 'cli1', 'service_type' => 'correctivo', 'status' => 'created', 'scheduled_at' => null, 'sla_deadline_at' => null, 'closed_at' => null, 'cancelled_at' => null, 'created_by' => 'user1']],
                [],
                [['employee_id' => 'emp1', 'avg_hours' => '4.5', 'cnt' => '10']],
            );

        $dbal->expects(self::exactly(11))->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                '3',
                '20',
                '2',
                '4',
                '15',
                '1',
                '12',
                '2',
                '8',
                '3',
                false,
            );

        $handler = new SupportDashboardMetricsHandler($dbal);
        $metrics = $handler();

        self::assertSame(15, $metrics->totalTickets);
        self::assertSame(5, $metrics->openTickets);
        self::assertSame(0, $metrics->assignedTickets);
        self::assertSame(0, $metrics->inProgressTickets);
        self::assertSame(0, $metrics->pendingClosure);
        self::assertSame(3, $metrics->overdueSla);
        self::assertSame(4, $metrics->completedToday);
        self::assertSame(15, $metrics->completedThisWeek);
        self::assertSame(0.0, $metrics->avgResolutionHours);
        self::assertSame(2, $metrics->slaBreachCount30d);
        self::assertSame(10.0, $metrics->slaBreachRate30d);
        self::assertSame(1, $metrics->reopenedCount);
        self::assertSame(8.3, round($metrics->reopenRate, 1));
        self::assertSame(2, $metrics->cancelledCount);
        self::assertSame(16.7, round($metrics->cancellationRate, 1));
        self::assertSame(8, $metrics->techniciansAvailable);
        self::assertSame(3, $metrics->techniciansBusy);
        self::assertCount(2, $metrics->statusBreakdown);
        self::assertCount(2, $metrics->topTechnicians);
        self::assertCount(1, $metrics->topClientsByTickets);
        self::assertCount(1, $metrics->completionsTrend);
        self::assertCount(1, $metrics->recentTickets);
        self::assertCount(1, $metrics->avgTimeByTechnician);
        self::assertSame('emp1', $metrics->avgTimeByTechnician[0]['employee_id']);
    }

    public function testCalculatesSlaRatesCorrectly(): void
    {
        $dbal = $this->createMock(Connection::class);

        $dbal->method('fetchAllAssociative')->willReturn([]);
        $dbal->method('fetchOne')->willReturnOnConsecutiveCalls(
            '5',
            '10',
            '1',
            '0',
            '0',
            '3',
            '20',
            '4',
            '0',
            '0',
            false,
        );

        $handler = new SupportDashboardMetricsHandler($dbal);
        $metrics = $handler();

        self::assertSame(5, $metrics->overdueSla);
        self::assertSame(1, $metrics->slaBreachCount30d);
        self::assertSame(10.0, $metrics->slaBreachRate30d);
        self::assertSame(3, $metrics->reopenedCount);
        self::assertSame(15.0, $metrics->reopenRate);
        self::assertSame(4, $metrics->cancelledCount);
        self::assertSame(20.0, $metrics->cancellationRate);
    }

    public function testCalculatesAvgResolutionHours(): void
    {
        $dbal = $this->createMock(Connection::class);

        $dbal->method('fetchAllAssociative')->willReturnCallback(function (string $sql) {
            if (str_contains($sql, 'service_type')) {
                return [['service_type' => 'correctivo', 'avg_hours' => '4.5', 'cnt' => '10']];
            }
            return [];
        });

        $dbal->method('fetchOne')->willReturnCallback(function (string $sql) {
            if (str_contains($sql, 'AVG(TIMESTAMPDIFF') && str_contains($sql, 'service_type')) {
                return '0';
            }
            if (str_contains($sql, 'AVG')) {
                return '4.5';
            }
            return '0';
        });

        $handler = new SupportDashboardMetricsHandler($dbal);
        $metrics = $handler();

        self::assertSame(4.5, $metrics->avgResolutionHours);
        self::assertCount(1, $metrics->avgResolutionByServiceType);
        self::assertSame('correctivo', $metrics->avgResolutionByServiceType[0]['service_type']);
    }
}
