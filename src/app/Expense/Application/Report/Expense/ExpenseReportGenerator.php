<?php

declare(strict_types=1);

namespace App\Expense\Application\Report\Expense;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class ExpenseReportGenerator implements ReportGeneratorInterface
{
    private const STATUS_LABELS = [
        'draft'     => 'Borrador',
        'submitted' => 'Enviado',
        'approved'  => 'Aprobado',
        'rejected'  => 'Rechazado',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Gastos por Período';
    }

    public function getHeaders(): array
    {
        return [
            'Fecha',
            'N° Gasto',
            'Empleado ID',
            'Motivo',
            'Estado',
            'Total Items',
            'Monto Total',
            'Creado',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['oe.deleted_at IS NULL'];

        if (!empty($filters['date_from'])) {
            $conditions[] = 'oe.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'oe.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'oe.employee_id = :employee_id';
            $params['employee_id'] = $filters['employee_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'oe.status = :status';
            $params['status'] = $filters['status'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                oe.id,
                oe.sequence,
                oe.employee_id,
                oe.reason,
                oe.status,
                oe.created_at,
                COUNT(ei.id) AS item_count,
                COALESCE(SUM(ei.amount), 0) AS total_amount
            FROM operating_expenses oe
            LEFT JOIN expense_items ei ON ei.expense_id = oe.id AND ei.deleted_at IS NULL
            WHERE {$where}
            GROUP BY oe.id, oe.sequence, oe.employee_id, oe.reason, oe.status, oe.created_at
            ORDER BY oe.created_at DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'Fecha'       => $row['created_at'] ? (new \DateTimeImmutable($row['created_at']))->format('d/m/Y H:i') : '—',
                'N° Gasto'    => (string) ($row['sequence'] ?? '—'),
                'Empleado ID' => $row['employee_id'] ?? '—',
                'Motivo'      => $row['reason'] !== null ? mb_substr($row['reason'], 0, 100) : '—',
                'Estado'      => self::STATUS_LABELS[$row['status']] ?? $row['status'],
                'Total Items' => (string) ($row['item_count'] ?? 0),
                'Monto Total' => '$ ' . number_format((float) ($row['total_amount'] ?? 0), 2),
                'Creado'      => $row['created_at'] ? (new \DateTimeImmutable($row['created_at']))->format('d/m/Y H:i') : '—',
            ];
        }

        return $result;
    }
}
