<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\StockOverview;

use Doctrine\DBAL\Connection;

/**
 * Consulta unificada de stock serializado y no serializado.
 *
 * Usa DBAL nativo para flexibilidad total en filtros + paginación
 * sin necesidad de ORM join entre dos entidades distintas.
 */
final readonly class StockOverviewHandler
{
    private const STATUS_LABELS = [
        'disponible'   => 'Disponible',
        'reservado'    => 'Reservado',
        'dañado'       => 'Dañado',
        'dado de baja' => 'Dado de baja',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function __invoke(StockOverviewQuery $query): StockOverviewResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        // ── Build unified query (UNION of serialized + nonserialized) ─────────
        [$serialSql,    $serialParams,    $serialTypes]    = $this->buildSerializedSql($query);
        [$nonSerialSql, $nonSerialParams, $nonSerialTypes] = $this->buildNonserializedSql($query);

        $allParams = array_merge($serialParams, $nonSerialParams);
        $allTypes  = array_merge($serialTypes, $nonSerialTypes);

        // Count totals for metrics (without LIMIT)
        $countSql = "SELECT status, SUM(quantity) as qty FROM ({$serialSql} UNION ALL {$nonSerialSql}) as stock GROUP BY status";
        $countRows = $this->connection->fetchAllAssociative($countSql, $allParams, $allTypes);

        $totalAvailable     = 0;
        $totalDamaged       = 0;
        $totalReserved      = 0;
        $totalDecommissioned = 0;
        $total              = 0;

        foreach ($countRows as $row) {
            $qty = (int) $row['qty'];
            $total += $qty;
            match ($row['status']) {
                'disponible'   => $totalAvailable      += $qty,
                'dañado'       => $totalDamaged         += $qty,
                'reservado'    => $totalReserved        += $qty,
                'dado de baja' => $totalDecommissioned  += $qty,
                default        => null,
            };
        }

        // Paginated items
        $itemsSql = "SELECT * FROM ({$serialSql} UNION ALL {$nonSerialSql}) as stock ORDER BY location_name ASC, product_name ASC, status ASC LIMIT {$query->perPage} OFFSET {$offset}";
        $rows = $this->connection->fetchAllAssociative($itemsSql, $allParams, $allTypes);

        $items = array_map(
            fn (array $r) => new StockItemResponse(
                id:           $r['id'],
                type:         $r['type'],
                productId:    $r['product_id'],
                productCode:  $r['product_code'],
                productName:  $r['product_name'],
                locationId:   $r['location_id'],
                locationName: $r['location_name'],
                status:       $r['status'],
                statusLabel:  self::STATUS_LABELS[$r['status']] ?? $r['status'],
                quantity:     (int) $r['quantity'],
                serialNumber: $r['serial_number'] ?: null,
            ),
            $rows
        );

        return new StockOverviewResult(
            items:               $items,
            total:               $total,
            page:                $query->page,
            perPage:             $query->perPage,
            totalAvailable:      $totalAvailable,
            totalDamaged:        $totalDamaged,
            totalReserved:       $totalReserved,
            totalDecommissioned: $totalDecommissioned,
        );
    }

    /** @return array{string, array, array} [sql, params, types] */
    private function buildSerializedSql(StockOverviewQuery $query): array
    {
        if ($query->type === 'nonserialized') {
            return ["SELECT NULL as id, 'serialized' as type, NULL as product_id, '' as product_code, '' as product_name, NULL as location_id, '' as location_name, '' as status, 1 as quantity, NULL as serial_number WHERE 1=0", [], []];
        }

        $sql = "
            SELECT
                ss.id,
                'serialized'      AS type,
                p.id              AS product_id,
                p.code            AS product_code,
                p.name            AS product_name,
                l.id              AS location_id,
                l.name            AS location_name,
                ss.status,
                1                 AS quantity,
                ss.serial_number
            FROM serialized_stock ss
            INNER JOIN products p ON p.id = ss.product_id AND p.deleted_at IS NULL
            INNER JOIN stock_locations l ON l.id = ss.location_id AND l.deleted_at IS NULL
            WHERE ss.deleted_at IS NULL
        ";

        $params = [];
        $types  = [];

        if ($query->locationId !== null) {
            $sql .= ' AND ss.location_id = :loc_id_s';
            $params['loc_id_s'] = $query->locationId;
        }
        if ($query->productId !== null) {
            $sql .= ' AND ss.product_id = :prod_id_s';
            $params['prod_id_s'] = $query->productId;
        }
        if ($query->status !== null) {
            $sql .= ' AND ss.status = :status_s';
            $params['status_s'] = $query->status;
        }
        if ($query->search !== null && $query->search !== '') {
            $sql .= ' AND (p.name LIKE :search_s OR p.code LIKE :search_s2 OR ss.serial_number LIKE :search_s3)';
            $like = '%' . $query->search . '%';
            $params['search_s']  = $like;
            $params['search_s2'] = $like;
            $params['search_s3'] = $like;
        }

        return [$sql, $params, $types];
    }

    /** @return array{string, array, array} [sql, params, types] */
    private function buildNonserializedSql(StockOverviewQuery $query): array
    {
        if ($query->type === 'serialized') {
            return ["SELECT NULL as id, 'nonserialized' as type, NULL as product_id, '' as product_code, '' as product_name, NULL as location_id, '' as location_name, '' as status, 0 as quantity, NULL as serial_number WHERE 1=0", [], []];
        }

        $sql = "
            SELECT
                ns.id,
                'nonserialized'   AS type,
                p.id              AS product_id,
                p.code            AS product_code,
                p.name            AS product_name,
                l.id              AS location_id,
                l.name            AS location_name,
                ns.status,
                ns.quantity,
                NULL              AS serial_number
            FROM nonserialized_stock ns
            INNER JOIN products p ON p.id = ns.product_id AND p.deleted_at IS NULL
            INNER JOIN stock_locations l ON l.id = ns.location_id AND l.deleted_at IS NULL
            WHERE ns.deleted_at IS NULL AND ns.quantity > 0
        ";

        $params = [];
        $types  = [];

        if ($query->locationId !== null) {
            $sql .= ' AND ns.location_id = :loc_id_n';
            $params['loc_id_n'] = $query->locationId;
        }
        if ($query->productId !== null) {
            $sql .= ' AND ns.product_id = :prod_id_n';
            $params['prod_id_n'] = $query->productId;
        }
        if ($query->status !== null) {
            $sql .= ' AND ns.status = :status_n';
            $params['status_n'] = $query->status;
        }
        if ($query->search !== null && $query->search !== '') {
            $sql .= ' AND (p.name LIKE :search_n OR p.code LIKE :search_n2)';
            $like = '%' . $query->search . '%';
            $params['search_n']  = $like;
            $params['search_n2'] = $like;
        }

        return [$sql, $params, $types];
    }
}
