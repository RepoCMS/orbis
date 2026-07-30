<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetOrganizationDashboard;

use Doctrine\DBAL\Connection;

final readonly class GetOrganizationDashboardHandler
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(GetOrganizationDashboardQuery $query): array
    {
        $employeesByStatus = $this->employeesByStatus();
        $activeCount    = $employeesByStatus['active'] ?? 0;
        $onVacationCount = $employeesByStatus['on_vacation'] ?? 0;
        $inactiveCount  = $employeesByStatus['inactive'] ?? 0;
        $total          = $activeCount + $onVacationCount + $inactiveCount;

        return [
            'totalEmployees'       => $total,
            'active'               => $activeCount,
            'onVacation'           => $onVacationCount,
            'onDuty'               => $activeCount,
            'inactive'             => $inactiveCount,
            'totalZones'           => $this->countActiveZones(),
            'employeesByZone'      => $this->employeesByZone(),
            'zoneNames'            => $this->zoneNames(),
            'overtimeByEmployee'   => $this->overtimeByEmployee(),
            'avgTenureByProfile'   => $this->avgTenureByProfile(),
            'upcomingVacations'    => $this->upcomingVacations(),
            'availableTechnicians' => $this->countAvailableTechnicians(),
            'totalTechnicians'     => $this->countTotalTechnicians(),
        ];
    }

    /** @return array<string, int> */
    private function employeesByStatus(): array
    {
        $sql = "
            SELECT status, COUNT(*) AS total
            FROM employees
            WHERE deleted_at IS NULL
            GROUP BY status
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $result = ['active' => 0, 'on_vacation' => 0, 'inactive' => 0];
        foreach ($rows as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
        }
        return $result;
    }

    private function countActiveZones(): int
    {
        $sql = "SELECT COUNT(*) FROM service_zones WHERE status = 'active' AND deleted_at IS NULL";
        return (int) $this->connection->fetchOne($sql);
    }

    /** @return array<string, int> */
    private function employeesByZone(): array
    {
        $sql = "
            SELECT service_zone_id, COUNT(*) AS total
            FROM employees
            WHERE deleted_at IS NULL
              AND service_zone_id IS NOT NULL
            GROUP BY service_zone_id
            ORDER BY total DESC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['service_zone_id']] = (int) $row['total'];
        }
        return $result;
    }

    /** @return array<string, string> */
    private function zoneNames(): array
    {
        $sql = "
            SELECT id, name
            FROM service_zones
            WHERE deleted_at IS NULL
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['id']] = (string) $row['name'];
        }
        return $result;
    }

    /**
     * Horas extras aprobadas del período actual.
     * @return array<array{employeeId: string, firstName: string, lastName: string, records: int, totalHours: float}>
     */
    private function overtimeByEmployee(): array
    {
        $sql = "
            SELECT
                otr.employee_id AS employeeId,
                e.first_name AS firstName,
                e.last_name AS lastName,
                COUNT(otr.id) AS records,
                COALESCE(SUM(TIMESTAMPDIFF(SECOND, otr.start_time, otr.end_time)) / 3600, 0) AS totalHours
            FROM org_overtime_records otr
            JOIN employees e ON otr.employee_id = e.id
            WHERE otr.status = 'approved'
              AND otr.worked_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
            GROUP BY otr.employee_id, e.first_name, e.last_name
            ORDER BY totalHours DESC
            LIMIT 10
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map(
            fn (array $row): array => [
                'employeeId' => (string) $row['employeeId'],
                'firstName'  => (string) $row['firstName'],
                'lastName'   => (string) $row['lastName'],
                'records'    => (int) $row['records'],
                'totalHours' => (float) $row['totalHours'],
            ],
            $rows
        );
    }

    /**
     * Antigüedad promedio por perfil.
     * @return array<array{profile: string, label: string, total: int, avgDays: float, avgYears: float}>
     */
    private function avgTenureByProfile(): array
    {
        $sql = "
            SELECT
                profile,
                COUNT(*) AS total,
                COALESCE(AVG(DATEDIFF(NOW(), hired_at)), 0) AS avgDays
            FROM employees
            WHERE deleted_at IS NULL
            GROUP BY profile
            ORDER BY avgDays DESC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        $labelMap = [
            'technician'     => 'Técnico',
            'coordinator'    => 'Coordinador',
            'administrative' => 'Administrativo',
        ];

        return array_map(
            fn (array $row): array => [
                'profile'  => (string) $row['profile'],
                'label'    => $labelMap[(string) $row['profile']] ?? (string) $row['profile'],
                'total'    => (int) $row['total'],
                'avgDays'  => (float) $row['avgDays'],
                'avgYears' => round((float) $row['avgDays'] / 365.25, 1),
            ],
            $rows
        );
    }

    /**
     * Vacaciones aprobadas en los próximos 30 días.
     * @return array<array{id: string, employeeId: string, firstName: string, lastName: string, startDate: string, endDate: string, durationDays: int}>
     */
    private function upcomingVacations(): array
    {
        $sql = "
            SELECT
                vr.id AS id,
                vr.employee_id AS employeeId,
                e.first_name AS firstName,
                e.last_name AS lastName,
                vr.start_date AS startDate,
                vr.end_date AS endDate,
                DATEDIFF(vr.end_date, vr.start_date) AS durationDays
            FROM org_vacation_requests vr
            JOIN employees e ON vr.employee_id = e.id
            WHERE vr.status = 'approved'
              AND vr.start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            ORDER BY vr.start_date ASC
        ";
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map(
            fn (array $row): array => [
                'id'           => (string) $row['id'],
                'employeeId'   => (string) $row['employeeId'],
                'firstName'    => (string) $row['firstName'],
                'lastName'     => (string) $row['lastName'],
                'startDate'    => $row['startDate'] instanceof \DateTimeInterface ? $row['startDate']->format('Y-m-d') : (string) $row['startDate'],
                'endDate'      => $row['endDate'] instanceof \DateTimeInterface ? $row['endDate']->format('Y-m-d') : (string) $row['endDate'],
                'durationDays' => (int) $row['durationDays'],
            ],
            $rows
        );
    }

    private function countAvailableTechnicians(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM employees e
            WHERE e.profile = 'technician'
              AND e.status = 'active'
              AND e.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM ticket_assignments ta
                  JOIN tickets t ON ta.ticket_id = t.id
                  WHERE ta.employee_id = e.id
                    AND t.deleted_at IS NULL
                    AND t.status NOT IN ('closed', 'cancelled')
              )
        ";
        return (int) $this->connection->fetchOne($sql);
    }

    private function countTotalTechnicians(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM employees
            WHERE profile = 'technician'
              AND deleted_at IS NULL
        ";
        return (int) $this->connection->fetchOne($sql);
    }
}
