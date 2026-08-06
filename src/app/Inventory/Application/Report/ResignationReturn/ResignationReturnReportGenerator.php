<?php

declare(strict_types=1);

namespace App\Inventory\Application\Report\ResignationReturn;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class ResignationReturnReportGenerator implements ReportGeneratorInterface
{
    private const LOAN_STATUS_LABELS = [
        'pending_loan' => 'Pendiente',
        'approved'     => 'Aprobado',
        'rejected'     => 'Rechazado',
        'cancelled'    => 'Cancelado',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Devolución por Renuncia';
    }

    public function getHeaders(): array
    {
        return [
            'Producto',
            'Código',
            'Serial',
            'Estado Préstamo',
            'Técnico Origen',
            'Técnico Destino',
            'Ticket Relacionado',
            'Notas',
            'Fecha Préstamo',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['tl.deleted_at IS NULL'];

        if (!empty($filters['status'])) {
            $conditions[] = 'tl.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['from_technician_id'])) {
            $conditions[] = 'tl.from_technician_id = :from_technician_id';
            $params['from_technician_id'] = $filters['from_technician_id'];
        }

        if (!empty($filters['to_technician_id'])) {
            $conditions[] = 'tl.to_technician_id = :to_technician_id';
            $params['to_technician_id'] = $filters['to_technician_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'tl.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'tl.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                tl.id,
                tl.serialized_stock_id,
                tl.from_technician_id,
                tl.to_technician_id,
                tl.ticket_id,
                tl.status,
                tl.notes,
                tl.created_at,
                p.name AS product_name,
                p.code AS product_code,
                ss.serial_number,
                from_emp.first_name AS from_first_name,
                from_emp.last_name AS from_last_name,
                to_emp.first_name AS to_first_name,
                to_emp.last_name AS to_last_name
            FROM technician_loans tl
            LEFT JOIN serialized_stock ss ON ss.id = tl.serialized_stock_id AND ss.deleted_at IS NULL
            LEFT JOIN products p ON p.id = ss.product_id AND p.deleted_at IS NULL
            LEFT JOIN employees from_emp ON from_emp.id = tl.from_technician_id AND from_emp.deleted_at IS NULL
            LEFT JOIN employees to_emp ON to_emp.id = tl.to_technician_id AND to_emp.deleted_at IS NULL
            WHERE {$where}
            ORDER BY tl.created_at DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $fromName = trim(($row['from_first_name'] ?? '') . ' ' . ($row['from_last_name'] ?? ''));
            $toName = trim(($row['to_first_name'] ?? '') . ' ' . ($row['to_last_name'] ?? ''));

            $result[] = [
                'Producto'           => $row['product_name'] ?? '—',
                'Código'             => $row['product_code'] ?? '—',
                'Serial'             => $row['serial_number'] ?? '—',
                'Estado Préstamo'    => self::LOAN_STATUS_LABELS[$row['status']] ?? $row['status'] ?? '—',
                'Técnico Origen'     => $fromName ?: '—',
                'Técnico Destino'    => $toName ?: '—',
                'Ticket Relacionado' => $row['ticket_id'] ?? '—',
                'Notas'              => $row['notes'] ?? '—',
                'Fecha Préstamo'     => $row['created_at'] ? (new \DateTimeImmutable($row['created_at']))->format('d/m/Y H:i') : '—',
            ];
        }

        return $result;
    }
}
