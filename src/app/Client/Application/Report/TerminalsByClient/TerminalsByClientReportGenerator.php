<?php

declare(strict_types=1);

namespace App\Client\Application\Report\TerminalsByClient;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class TerminalsByClientReportGenerator implements ReportGeneratorInterface
{
    private const TERMINAL_STATUS_LABELS = [
        'active'   => 'Activo',
        'inactive' => 'Inactivo',
    ];

    private const ASSIGNMENT_STATUS_LABELS = [
        'operative'   => 'Operativo',
        'inoperative' => 'Inoperativo',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Terminales por Cliente';
    }

    public function getHeaders(): array
    {
        return [
            'Cliente',
            'RUC',
            'Serial',
            'Tipo',
            'Modelo',
            'Estado',
            'Asignación',
            'ID en Cliente',
            'Sitio',
            'Estado Asignación',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['t.deleted_at IS NULL'];

        if (!empty($filters['client_id'])) {
            $conditions[] = 't.client_id = :client_id';
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 't.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['serial'])) {
            $conditions[] = 't.serial LIKE :serial';
            $params['serial'] = '%' . $filters['serial'] . '%';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                t.id,
                t.serial,
                t.type,
                t.atm_model_code,
                t.status,
                c.name AS client_name,
                c.ruc,
                ta.name AS assignment_name,
                ta.id_assigned_by_client,
                s.name AS site_name,
                ta.status AS assignment_status
            FROM client_terminals t
            LEFT JOIN client_clients c ON c.id = t.client_id AND c.deleted_at IS NULL
            LEFT JOIN client_terminal_assignments ta ON ta.terminal_id = t.id AND ta.deleted_at IS NULL
            LEFT JOIN client_sites s ON s.id = ta.site_id AND s.deleted_at IS NULL
            WHERE {$where}
            ORDER BY c.name ASC, t.serial ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'Cliente'           => $row['client_name'] ?? '—',
                'RUC'               => $row['ruc'] ?? '—',
                'Serial'            => $row['serial'],
                'Tipo'              => $row['type'] ?? '—',
                'Modelo'            => $row['atm_model_code'] ?? '—',
                'Estado'            => self::TERMINAL_STATUS_LABELS[$row['status']] ?? $row['status'] ?? '—',
                'Asignación'        => $row['assignment_name'] ?? 'Sin asignación',
                'ID en Cliente'     => $row['id_assigned_by_client'] ?? '—',
                'Sitio'             => $row['site_name'] ?? '—',
                'Estado Asignación' => $row['assignment_status'] !== null
                    ? (self::ASSIGNMENT_STATUS_LABELS[$row['assignment_status']] ?? $row['assignment_status'])
                    : '—',
            ];
        }

        return $result;
    }
}
