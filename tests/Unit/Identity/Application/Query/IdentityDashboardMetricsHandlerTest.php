<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Query\IdentityDashboardMetrics\IdentityDashboardMetricsHandler;
use App\Identity\Application\Query\IdentityDashboardMetrics\IdentityDashboardMetricsQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class IdentityDashboardMetricsHandlerTest extends TestCase
{
    public function testReturnsDashboardMetrics(): void
    {
        $conn = $this->createMock(Connection::class);

        $conn->method('fetchOne')->willReturnCallback(function (string $sql, array $params = []): mixed {
            if (str_contains($sql, 'locked_until')) {
                return 5;
            }
            if (str_contains($sql, 'identity_user_mfa_configs')) {
                return 30;
            }
            if (str_contains($sql, 'identity_refresh_tokens')) {
                return 42;
            }
            if (str_contains($sql, 'identity_permissions')) {
                return 20;
            }
            if (str_contains($sql, 'status') && ($params['status'] ?? '') === 'active') {
                return 65;
            }
            if (str_contains($sql, 'identity_users') && !str_contains($sql, 'status') && !str_contains($sql, 'locked')) {
                return 100;
            }
            return 0;
        });

        $conn->method('fetchAllAssociative')->willReturnCallback(function (string $sql, array $params = []): array {
            if (str_contains($sql, 'GROUP BY status')) {
                return [
                    ['status' => 'active', 'total' => 65],
                    ['status' => 'inactive', 'total' => 35],
                ];
            }
            if (str_contains($sql, ':days')) {
                return [
                    ['event' => 'login_success', 'total' => 120],
                    ['event' => 'login_failed', 'total' => 8],
                ];
            }
            if (str_contains($sql, 'identity_roles')) {
                return [
                    ['role_name' => 'Admin', 'total' => 3],
                    ['role_name' => 'Technician', 'total' => 50],
                ];
            }
            if (str_contains($sql, 'auth_logs')) {
                return [
                    ['date' => '2024-01-15', 'total' => 2],
                ];
            }
            return [];
        });

        $handler = new IdentityDashboardMetricsHandler($conn);
        $metrics = ($handler)(new IdentityDashboardMetricsQuery(recentDays: 7));

        self::assertSame(100, $metrics->totalUsers);
        self::assertSame(65, $metrics->activeUsers);
        self::assertSame(5, $metrics->lockedUsers);
        self::assertSame(30, $metrics->mfaEnabledCount);
        self::assertSame(30.0, $metrics->mfaAdoptionRate);
        self::assertSame(42, $metrics->activeSessions);
        self::assertSame(20, $metrics->totalPermissions);
        self::assertSame(8, $metrics->failedLoginsLast7d());
        self::assertSame(120, $metrics->successfulLoginsLast7d());
        self::assertSame(93.8, $metrics->loginSuccessRate());
        self::assertCount(2, $metrics->usersByStatus);
        self::assertCount(2, $metrics->loginEventsLast7d);
        self::assertCount(2, $metrics->usersByRole);
        self::assertCount(1, $metrics->securityEventsLast24h);
    }
}
