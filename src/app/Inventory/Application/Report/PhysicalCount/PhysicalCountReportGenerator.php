<?php

declare(strict_types=1);

namespace App\Inventory\Application\Report\PhysicalCount;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class PhysicalCountReportGenerator implements ReportGeneratorInterface
{
    private const STATUS_LABELS = [
        'draft'            => 'Borrador',
        'pending_approval' => 'Pendiente Aprobación',
        'approved'         => 'Aprobado',
        'rejected'         => 'Rechazado',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    public function getTitle(): string
    {
        return 'Conteos de Inventario';
    }

    public function getHeaders(): array
    {
        return [
            'Ubicación',
            'Contado por',
            'Estado',
            'Total Ítems',
            'Ítems con Diferencia',
            'Total Diferencia',
            'Aprobado por',
            'Notas',
            'Fecha Creación',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['pc.deleted_at IS NULL'];

        if (!empty($filters['status'])) {
            $conditions[] = 'pc.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['location_id'])) {
            $conditions[] = 'pc.location_id = :location_id';
            $params['location_id'] = $filters['location_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'pc.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'pc.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                pc.id,
                pc.location_id,
                pc.counted_by,
                pc.status,
                pc.notes,
                pc.approved_by,
                pc.created_at,
                l.name AS location_name,
                emp.first_name,
                emp.last_name,
                (
                    SELECT COUNT(*)
                    FROM stock_physical_count_items pci
                    WHERE pci.physical_count_id = pc.id
                ) AS total_items,
                (
                    SELECT COUNT(*)
                    FROM stock_physical_count_items pci
                    WHERE pci.physical_count_id = pc.id
                      AND (pci.counted_qty - pci.expected_qty) != 0
                ) AS items_with_diff,
                (
                    SELECT COALESCE(SUM(pci.counted_qty - pci.expected_qty), 0)
                    FROM stock_physical_count_items pci
                    WHERE pci.physical_count_id = pc.id
                ) AS total_difference
            FROM stock_physical_counts pc
            LEFT JOIN stock_locations l ON l.id = pc.location_id AND l.deleted_at IS NULL
            LEFT JOIN employees emp ON emp.id = pc.counted_by AND emp.deleted_at IS NULL
            WHERE {$where}
            ORDER BY pc.created_at DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $countedByName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

            $result[] = [
                'Ubicación'            => $row['location_name'] ?? $row['location_id'] ?? '—',
                'Contado por'          => $countedByName ?: $row['counted_by'] ?? '—',
                'Estado'               => self::STATUS_LABELS[$row['status']] ?? $row['status'] ?? '—',
                'Total Ítems'          => (string) ($row['total_items'] ?? 0),
                'Ítems con Diferencia' => (string) ($row['items_with_diff'] ?? 0),
                'Total Diferencia'     => (string) ($row['total_difference'] ?? 0),
                'Aprobado por'         => $row['approved_by'] ?? '—',
                'Notas'                => $row['notes'] ?? '—',
                'Fecha Creación'       => $row['created_at'] ? (new \DateTimeImmutable($row['created_at']))->format('d/m/Y H:i') : '—',
            ];
        }

        return $result;
    }
}
