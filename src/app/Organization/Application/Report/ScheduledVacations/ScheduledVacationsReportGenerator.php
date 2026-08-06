<?php

declare(strict_types=1);

namespace App\Organization\Application\Report\ScheduledVacations;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class ScheduledVacationsReportGenerator implements ReportGeneratorInterface
{
    private const PROFILE_LABELS = [
        'technician_field'       => 'Técnico de Campo',
        'supervisor_technician'  => 'Supervisor Técnico',
        'warehouse_worker'       => 'Bodeguero',
        'office_staff'           => 'Personal Oficina',
        'admin'                  => 'Administrador',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Vacaciones Programadas';
    }

    public function getHeaders(): array
    {
        return [
            'Empleado',
            'Perfil',
            'Fecha Inicio',
            'Fecha Fin',
            'Días',
            'Estado',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['vr.deleted_at IS NULL'];

        $conditions[] = "vr.status = 'approved'";

        if (!empty($filters['date_from'])) {
            $conditions[] = 'vr.start_date >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'vr.end_date <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'vr.employee_id = :employee_id';
            $params['employee_id'] = $filters['employee_id'];
        }

        if (!empty($filters['profile'])) {
            $conditions[] = 'e.profile = :profile';
            $params['profile'] = $filters['profile'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                vr.id,
                vr.start_date,
                vr.end_date,
                vr.status,
                e.first_name,
                e.last_name,
                e.profile
            FROM org_vacation_requests vr
            LEFT JOIN employees e ON e.id = vr.employee_id AND e.deleted_at IS NULL
            WHERE {$where}
            ORDER BY vr.start_date ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $startDate = $row['start_date'] ? new \DateTimeImmutable($row['start_date']) : null;
            $endDate = $row['end_date'] ? new \DateTimeImmutable($row['end_date']) : null;
            $days = $startDate && $endDate ? $startDate->diff($endDate)->days + 1 : 0;

            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

            $result[] = [
                'Empleado'     => $fullName ?: '—',
                'Perfil'       => self::PROFILE_LABELS[$row['profile']] ?? $row['profile'] ?? '—',
                'Fecha Inicio' => $startDate ? $startDate->format('d/m/Y') : '—',
                'Fecha Fin'    => $endDate ? $endDate->format('d/m/Y') : '—',
                'Días'         => (string) $days,
                'Estado'       => $row['status'] === 'approved' ? 'Aprobado' : ($row['status'] ?? '—'),
            ];
        }

        return $result;
    }
}
