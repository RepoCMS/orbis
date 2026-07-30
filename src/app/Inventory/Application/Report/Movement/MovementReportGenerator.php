<?php

declare(strict_types=1);

namespace App\Inventory\Application\Report\Movement;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class MovementReportGenerator implements ReportGeneratorInterface
{
    private const TYPE_LABELS = [
        'entry'      => 'Entrada',
        'transfer'   => 'Traslado',
        'exit'       => 'Salida',
        'loan'       => 'Préstamo',
        'adjustment' => 'Ajuste',
    ];

    private const STATUS_LABELS = [
        'pending'    => 'Pendiente',
        'in_transit' => 'En tránsito',
        'completed'  => 'Completado',
        'cancelled'  => 'Cancelado',
        'rejected'   => 'Rechazado',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    public function getTitle(): string
    {
        return 'Movimientos de Stock';
    }

    public function getHeaders(): array
    {
        return [
            'Fecha',
            'Tipo',
            'Estado',
            'Origen',
            'Destino',
            'Producto',
            'Código',
            'Serial / Cantidad',
            'Creado por',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['m.deleted_at IS NULL'];

        if (!empty($filters['date_from'])) {
            $conditions[] = 'm.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'm.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['type'])) {
            $conditions[] = 'm.type = :type';
            $params['type'] = $filters['type'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                m.created_at,
                m.type,
                m.status,
                m.quantity,
                m.source_location_id,
                m.destination_location_id,
                m.created_by_user_id,
                sl.name AS source_location_name,
                dl.name AS destination_location_name,
                p.name AS product_name,
                p.code AS product_code,
                ss.serial_number
            FROM stock_movements m
            LEFT JOIN products p ON p.id = m.product_id AND p.deleted_at IS NULL
            LEFT JOIN stock_locations sl ON sl.id = m.source_location_id AND sl.deleted_at IS NULL
            LEFT JOIN stock_locations dl ON dl.id = m.destination_location_id AND dl.deleted_at IS NULL
            LEFT JOIN serialized_stock ss ON ss.id = m.serialized_stock_id AND ss.deleted_at IS NULL
            WHERE {$where}
            ORDER BY m.created_at DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $serialOrQty = $row['serial_number'] ?? null;
            if ($serialOrQty === null) {
                $serialOrQty = (string) ($row['quantity'] ?? '');
            }

            $result[] = [
                'Fecha'           => $row['created_at'] ? (new \DateTimeImmutable($row['created_at']))->format('d/m/Y H:i') : '—',
                'Tipo'            => self::TYPE_LABELS[$row['type']] ?? $row['type'],
                'Estado'          => self::STATUS_LABELS[$row['status']] ?? $row['status'],
                'Origen'          => $row['source_location_name'] ?? '—',
                'Destino'         => $row['destination_location_name'] ?? '—',
                'Producto'        => $row['product_name'] ?? '—',
                'Código'          => $row['product_code'] ?? '—',
                'Serial / Cantidad' => $serialOrQty,
                'Creado por'      => $row['created_by_user_id'] ?? '—',
            ];
        }

        return $result;
    }
}
