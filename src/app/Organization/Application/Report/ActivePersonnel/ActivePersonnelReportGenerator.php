<?php

declare(strict_types=1);

namespace App\Organization\Application\Report\ActivePersonnel;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class ActivePersonnelReportGenerator implements ReportGeneratorInterface
{
    private const PROFILE_LABELS = [
        'technician_field'       => 'Técnico de Campo',
        'supervisor_technician'  => 'Supervisor Técnico',
        'warehouse_worker'       => 'Bodeguero',
        'office_staff'           => 'Personal Oficina',
        'admin'                  => 'Administrador',
    ];

    private const STATUS_LABELS = [
        'active'      => 'Activo',
        'on_vacation' => 'Vacaciones',
        'inactive'    => 'Inactivo',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    public function getTitle(): string
    {
        return 'Personal Activo';
    }

    public function getHeaders(): array
    {
        return [
            'Nombre Completo',
            'Identificación',
            'Perfil',
            'Estado',
            'Zona',
            'Email',
            'Teléfono',
            'Fecha Ingreso',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['e.deleted_at IS NULL'];

        if (!empty($filters['profile'])) {
            $conditions[] = 'e.profile = :profile';
            $params['profile'] = $filters['profile'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'e.status = :status';
            $params['status'] = $filters['status'];
        } else {
            $conditions[] = "e.status IN ('active', 'on_vacation')";
        }

        if (!empty($filters['service_zone_id'])) {
            $conditions[] = 'e.service_zone_id = :service_zone_id';
            $params['service_zone_id'] = $filters['service_zone_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(e.first_name LIKE :search OR e.last_name LIKE :search2 OR e.identification_number LIKE :search3)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
            $params['search3'] = '%' . $filters['search'] . '%';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                e.first_name,
                e.last_name,
                e.identification_number,
                e.profile,
                e.status,
                e.email,
                e.phone_number,
                e.hired_at,
                sz.name AS service_zone_name
            FROM employees e
            LEFT JOIN service_zones sz ON sz.id = e.service_zone_id AND sz.deleted_at IS NULL
            WHERE {$where}
            ORDER BY e.last_name ASC, e.first_name ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

            $result[] = [
                'Nombre Completo' => $fullName ?: '—',
                'Identificación'  => $row['identification_number'] ?? '—',
                'Perfil'          => self::PROFILE_LABELS[$row['profile']] ?? $row['profile'] ?? '—',
                'Estado'          => self::STATUS_LABELS[$row['status']] ?? $row['status'] ?? '—',
                'Zona'            => $row['service_zone_name'] ?? '—',
                'Email'           => $row['email'] ?? '—',
                'Teléfono'        => $row['phone_number'] ?? '—',
                'Fecha Ingreso'   => $row['hired_at'] ? (new \DateTimeImmutable($row['hired_at']))->format('d/m/Y') : '—',
            ];
        }

        return $result;
    }
}
