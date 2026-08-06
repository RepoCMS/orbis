<?php

declare(strict_types=1);

namespace App\Expense\Application\Report\Advance;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class AdvanceReportGenerator implements ReportGeneratorInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Adelantos Pendientes';
    }

    public function getHeaders(): array
    {
        return [
            'N° Adelanto',
            'Empleado ID',
            'Monto',
            'Fecha Entrega',
            'Días Vencido',
            'Estado',
            'Dio',
            'Comprobante',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = ['ca.deleted_at IS NULL', "ca.status = 'outstanding'"];

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'ca.employee_id = :employee_id';
            $params['employee_id'] = $filters['employee_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'ca.given_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'ca.given_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                ca.advance_number,
                ca.employee_id,
                ca.amount,
                ca.given_at,
                ca.given_by,
                ca.voucher_path,
                ca.status
            FROM cash_advances ca
            WHERE {$where}
            ORDER BY ca.given_at ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        $now = new \DateTimeImmutable();
        foreach ($rows as $row) {
            $givenAt = $row['given_at'] ? new \DateTimeImmutable($row['given_at']) : null;
            $daysOverdue = $givenAt ? (int) $givenAt->diff($now)->days : 0;

            $result[] = [
                'N° Adelanto'  => $row['advance_number'] ?? '—',
                'Empleado ID'  => $row['employee_id'] ?? '—',
                'Monto'        => '$ ' . number_format((float) ($row['amount'] ?? 0), 2),
                'Fecha Entrega' => $givenAt ? $givenAt->format('d/m/Y') : '—',
                'Días Vencido' => (string) $daysOverdue,
                'Estado'       => $row['status'] === 'outstanding' ? 'Pendiente' : $row['status'],
                'Dio'          => $row['given_by'] ?? '—',
                'Comprobante'  => $row['voucher_path'] ?? '—',
            ];
        }

        return $result;
    }
}
