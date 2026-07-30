<?php

declare(strict_types=1);

namespace App\Support\Application\Command\MonitorSla;

/**
 * Resultado del monitoreo de SLA.
 * Devuelve estadísticas de las alertas generadas.
 */
final readonly class MonitorSlaResult
{
    public function __construct(
        public int $totalTickets,
        public int $aboutToExpireAlerts,
        public int $criticalAlerts,
        public int $breachedAlerts,
    ) {
    }

    public function totalAlerts(): int
    {
        return $this->aboutToExpireAlerts + $this->criticalAlerts + $this->breachedAlerts;
    }
}
