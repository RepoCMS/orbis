<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\IdentityDashboardMetrics;

final class IdentityDashboardMetrics
{
    /**
     * @param array<array{status: string, total: int}> $usersByStatus
     * @param array<array{event: string, total: int}> $loginEventsLast7d
     * @param array<array{role_name: string, total: int}> $usersByRole
     * @param array<array{date: string, total: int}> $securityEventsLast24h
     */
    public function __construct(
        public readonly int   $totalUsers,
        public readonly int   $activeUsers,
        public readonly int   $lockedUsers,
        public readonly int   $mfaEnabledCount,
        public readonly float $mfaAdoptionRate,
        public readonly int   $activeSessions,
        public readonly array $usersByStatus,
        public readonly array $loginEventsLast7d,
        public readonly array $usersByRole,
        public readonly array $securityEventsLast24h,
        public readonly int   $totalPermissions,
    ) {
    }

    public function failedLoginsLast7d(): int
    {
        $failed = array_filter(
            $this->loginEventsLast7d,
            static fn (array $row) => $row['event'] === 'login_failed',
        );

        return array_sum(array_column($failed, 'total'));
    }

    public function successfulLoginsLast7d(): int
    {
        $success = array_filter(
            $this->loginEventsLast7d,
            static fn (array $row) => $row['event'] === 'login_success',
        );

        return array_sum(array_column($success, 'total'));
    }

    public function loginSuccessRate(): float
    {
        $total = $this->successfulLoginsLast7d() + $this->failedLoginsLast7d();

        return $total > 0 ? round(($this->successfulLoginsLast7d() / $total) * 100, 1) : 0.0;
    }
}
