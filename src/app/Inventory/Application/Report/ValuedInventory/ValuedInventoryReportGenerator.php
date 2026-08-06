<?php

declare(strict_types=1);

namespace App\Inventory\Application\Report\ValuedInventory;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class ValuedInventoryReportGenerator implements ReportGeneratorInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Inventario Valorizado';
    }

    public function getHeaders(): array
    {
        return [
            'Producto',
            'Código',
            'Tipo',
            'Ubicación',
            'Serial',
            'Cantidad',
            'Estado',
            'P/U ($)',
            'Total ($)',
        ];
    }

    public function generate(array $filters = []): array
    {
        $rows = [];
        $totalValue = 0;

        $serialized = $this->getSerializedStock();
        foreach ($serialized as $row) {
            $price = (float) ($row['last_purchase_price'] ?? 0);
            $total = $price * 1;
            $totalValue += $total;
            $rows[] = [
                'Producto'  => $row['product_name'],
                'Código'    => $row['product_code'],
                'Tipo'      => 'Serializado',
                'Ubicación' => $row['location_name'],
                'Serial'    => $row['serial_number'],
                'Cantidad'  => 1,
                'Estado'    => $row['status'],
                'P/U ($)'   => number_format($price, 2),
                'Total ($)' => number_format($total, 2),
            ];
        }

        $nonSerialized = $this->getNonSerializedStock();
        foreach ($nonSerialized as $row) {
            $price = (float) ($row['last_purchase_price'] ?? 0);
            $qty = (int) $row['quantity'];
            $total = $price * $qty;
            $totalValue += $total;
            $rows[] = [
                'Producto'  => $row['product_name'],
                'Código'    => $row['product_code'],
                'Tipo'      => 'No serializado',
                'Ubicación' => $row['location_name'],
                'Serial'    => '—',
                'Cantidad'  => $qty,
                'Estado'    => $row['status'],
                'P/U ($)'   => number_format($price, 2),
                'Total ($)' => number_format($total, 2),
            ];
        }

        $rows[] = [
            'Producto'  => '',
            'Código'    => '',
            'Tipo'      => '',
            'Ubicación' => 'TOTAL GENERAL',
            'Serial'    => '',
            'Cantidad'  => '',
            'Estado'    => '',
            'P/U ($)'   => '',
            'Total ($)' => number_format($totalValue, 2),
        ];

        return $rows;
    }

    /** @return array<array<string, mixed>> */
    private function getSerializedStock(): array
    {
        $sql = '
            SELECT
                p.name AS product_name,
                p.code AS product_code,
                l.name AS location_name,
                ss.serial_number,
                ss.status,
                p.last_purchase_price
            FROM serialized_stock ss
            INNER JOIN products p ON p.id = ss.product_id AND p.deleted_at IS NULL
            INNER JOIN stock_locations l ON l.id = ss.location_id AND l.deleted_at IS NULL
            WHERE ss.deleted_at IS NULL
            ORDER BY p.name, l.name, ss.serial_number
        ';

        return $this->connection->fetchAllAssociative($sql);
    }

    /** @return array<array<string, mixed>> */
    private function getNonSerializedStock(): array
    {
        $sql = '
            SELECT
                p.name AS product_name,
                p.code AS product_code,
                l.name AS location_name,
                ns.quantity,
                ns.status,
                p.last_purchase_price
            FROM nonserialized_stock ns
            INNER JOIN products p ON p.id = ns.product_id AND p.deleted_at IS NULL
            INNER JOIN stock_locations l ON l.id = ns.location_id AND l.deleted_at IS NULL
            WHERE ns.deleted_at IS NULL
            ORDER BY p.name, l.name
        ';

        return $this->connection->fetchAllAssociative($sql);
    }
}
