<?php

declare(strict_types=1);

namespace App\Organization\Application\Report\Overtime;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class OvertimeReportGenerator implements ReportGeneratorInterface
{
    private const STATUS_LABELS = [
        'pending'  => 'Pendiente',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Horas Extras por Período';
    }

    public function getHeaders(): array
    {
        return [
            'Empleado',
            'Identificación',
            'Perfil',
            'Fecha Trabajada',
            'Hora Inicio',
            'Hora Fin',
            'Horas',
            'Motivo',
            'Estado',
            'Aprobado por',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['or_.deleted_at IS NULL'];

        $conditions[] = "or_.status = 'approved'";

        if (!empty($filters['date_from'])) {
            $conditions[] = 'or_.worked_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'or_.worked_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'or_.employee_id = :employee_id';
            $params['employee_id'] = $filters['employee_id'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                or_.id,
                or_.employee_id,
                or_.worked_at,
                or_.start_time,
                or_.end_time,
                or_.reason,
                or_.status,
                or_.reviewed_by,
                e.first_name,
                e.last_name,
                e.identification_number,
                e.profile,
                rev.first_name AS reviewer_first_name,
                rev.last_name AS reviewer_last_name
            FROM org_overtime_records or_
            LEFT JOIN employees e ON e.id = or_.employee_id AND e.deleted_at IS NULL
            LEFT JOIN employees rev ON rev.id = or_.reviewed_by AND rev.deleted_at IS NULL
            WHERE {$where}
            ORDER BY or_.worked_at DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $reviewerName = trim(($row['reviewer_first_name'] ?? '') . ' ' . ($row['reviewer_last_name'] ?? ''));

            $startTime = $row['start_time'] ? new \DateTimeImmutable($row['start_time']) : null;
            $endTime = $row['end_time'] ? new \DateTimeImmutable($row['end_time']) : null;

            $hours = 0;
            if ($startTime && $endTime) {
                $diff = $startTime->diff($endTime);
                $hours = $diff->h + round($diff->i / 60, 1);
            }

            $result[] = [
                'Empleado'       => $fullName ?: '—',
                'Identificación' => $row['identification_number'] ?? '—',
                'Perfil'         => $row['profile'] ?? '—',
                'Fecha Trabajada' => $row['worked_at'] ? (new \DateTimeImmutable($row['worked_at']))->format('d/m/Y') : '—',
                'Hora Inicio'    => $startTime ? $startTime->format('H:i') : '—',
                'Hora Fin'       => $endTime ? $endTime->format('H:i') : '—',
                'Horas'          => (string) $hours,
                'Motivo'         => $row['reason'] !== null ? mb_substr($row['reason'], 0, 150) : '—',
                'Estado'         => self::STATUS_LABELS[$row['status']] ?? $row['status'] ?? '—',
                'Aprobado por'   => $reviewerName ?: '—',
            ];
        }

        return $result;
    }
}
