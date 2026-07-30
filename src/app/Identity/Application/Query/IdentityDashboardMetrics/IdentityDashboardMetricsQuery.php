<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\IdentityDashboardMetrics;

final readonly class IdentityDashboardMetricsQuery
{
    public function __construct(
        public int $recentDays = 7,
    ) {}
}
