<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\IdentityDashboardMetrics;

use Doctrine\DBAL\Connection;

final readonly class IdentityDashboardMetricsHandler
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(IdentityDashboardMetricsQuery $query): IdentityDashboardMetrics
    {
        $totalUsers = $this->countUsers();
        $mfaEnabled = $this->countMfaEnabled();
        $mfaRate = $totalUsers > 0 ? round(($mfaEnabled / $totalUsers) * 100, 1) : 0.0;

        return new IdentityDashboardMetrics(
            totalUsers: $totalUsers,
            activeUsers: $this->countUsersByStatus('active'),
            lockedUsers: $this->countLockedUsers(),
            mfaEnabledCount: $mfaEnabled,
            mfaAdoptionRate: $mfaRate,
            activeSessions: $this->countActiveSessions(),
            usersByStatus: $this->usersByStatus(),
            loginEventsLast7d: $this->loginEventsLast7d($query->recentDays),
            usersByRole: $this->usersByRole(),
            securityEventsLast24h: $this->securityEventsLast24h(),
            totalPermissions: $this->countPermissions(),
        );
    }

    private function countUsers(): int
    {
        $sql = 'SELECT COUNT(*) FROM identity_users WHERE deleted_at IS NULL';
        return (int) $this->connection->fetchOne($sql);
    }

    private function countUsersByStatus(string $status): int
    {
        $sql = 'SELECT COUNT(*) FROM identity_users WHERE status = :status AND deleted_at IS NULL';
        return (int) $this->connection->fetchOne($sql, ['status' => $status]);
    }

    private function countLockedUsers(): int
    {
        $sql = 'SELECT COUNT(*) FROM identity_users WHERE locked_until > NOW() AND deleted_at IS NULL';
        return (int) $this->connection->fetchOne($sql);
    }

    private function countMfaEnabled(): int
    {
        $sql = 'SELECT COUNT(*) FROM identity_user_mfa_configs WHERE is_enabled = 1';
        return (int) $this->connection->fetchOne($sql);
    }

    private function countActiveSessions(): int
    {
        $sql = 'SELECT COUNT(*) FROM identity_refresh_tokens WHERE expires_at > NOW() AND revoked_at IS NULL';
        return (int) $this->connection->fetchOne($sql);
    }

    /** @return array<array{status: string, total: int}> */
    private function usersByStatus(): array
    {
        $sql = '
            SELECT status, COUNT(*) as total
            FROM identity_users
            WHERE deleted_at IS NULL
            GROUP BY status
            ORDER BY total DESC
        ';
        return $this->connection->fetchAllAssociative($sql);
    }

    /** @return array<array{event: string, total: int}> */
    private function loginEventsLast7d(int $days): array
    {
        $sql = '
            SELECT event, COUNT(*) as total
            FROM identity_auth_logs
            WHERE event IN (:login_success, :login_failed)
              AND occurred_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY event
        ';
        return $this->connection->fetchAllAssociative($sql, [
            'login_success' => 'login_success',
            'login_failed' => 'login_failed',
            'days' => $days,
        ]);
    }

    /** @return array<array{role_name: string, total: int}> */
    private function usersByRole(): array
    {
        $sql = '
            SELECT r.name as role_name, COUNT(u.id) as total
            FROM identity_users u
            INNER JOIN identity_roles r ON r.id = u.role_id
            WHERE u.deleted_at IS NULL
            GROUP BY r.name
            ORDER BY total DESC
        ';
        return $this->connection->fetchAllAssociative($sql);
    }

    /** @return array<array{date: string, total: int}> */
    private function securityEventsLast24h(): array
    {
        $sql = '
            SELECT DATE(occurred_at) as date, COUNT(*) as total
            FROM identity_auth_logs
            WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND event NOT IN (:login_success, :login_failed)
            GROUP BY DATE(occurred_at)
            ORDER BY date DESC
        ';
        return $this->connection->fetchAllAssociative($sql, [
            'login_success' => 'login_success',
            'login_failed' => 'login_failed',
        ]);
    }

    private function countPermissions(): int
    {
        $sql = 'SELECT COUNT(*) FROM identity_permissions WHERE deleted_at IS NULL';
        return (int) $this->connection->fetchOne($sql);
    }
}
