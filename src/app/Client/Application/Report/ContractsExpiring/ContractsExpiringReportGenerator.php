<?php

declare(strict_types=1);

namespace App\Client\Application\Report\ContractsExpiring;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class ContractsExpiringReportGenerator implements ReportGeneratorInterface
{
    private const STATUS_LABELS = [
        'active'    => 'Activo',
        'cancelled' => 'Cancelado',
        'expired'   => 'Vencido',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Contratos por Vencer';
    }

    public function getHeaders(): array
    {
        return [
            'Cliente',
            'RUC',
            'Tipo Contrato',
            'Fecha Inicio',
            'Fecha Fin',
            'Días Restantes',
            'Contacto Principal',
            'Email Contacto',
            'Teléfono Contacto',
            'Estado',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = [];

        $conditions[] = "cc.status = 'active'";
        $conditions[] = 'cc.end_date IS NOT NULL';
        $conditions[] = 'cc.end_date >= NOW()';
        $conditions[] = 'cc.end_date <= DATE_ADD(NOW(), INTERVAL 90 DAY)';

        if (!empty($filters['client_id'])) {
            $conditions[] = 'cc.client_id = :client_id';
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'cc.end_date >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'cc.end_date <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

        $sql = "
            SELECT
                cc.id,
                cc.client_id,
                cc.start_date,
                cc.end_date,
                cc.terms,
                cc.status,
                c.name AS client_name,
                c.ruc,
                co.first_name AS contact_first_name,
                co.last_name AS contact_last_name,
                co.email AS contact_email,
                co.phone_number AS contact_phone
            FROM client_contracts cc
            LEFT JOIN client_clients c ON c.id = cc.client_id AND c.deleted_at IS NULL
            LEFT JOIN client_contacts co ON co.client_id = cc.client_id AND co.status = 'active' AND co.deleted_at IS NULL
            WHERE {$where}
            ORDER BY cc.end_date ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $endDate = $row['end_date'] ? new \DateTimeImmutable($row['end_date']) : null;
            $now = new \DateTimeImmutable();
            $daysRemaining = $endDate ? $now->diff($endDate)->days : 0;

            $contactName = trim(($row['contact_first_name'] ?? '') . ' ' . ($row['contact_last_name'] ?? ''));

            $result[] = [
                'Cliente'           => $row['client_name'] ?? '—',
                'RUC'               => $row['ruc'] ?? '—',
                'Tipo Contrato'     => $row['terms'] !== null ? mb_substr($row['terms'], 0, 80) : 'Estándar',
                'Fecha Inicio'      => $row['start_date'] ? (new \DateTimeImmutable($row['start_date']))->format('d/m/Y') : '—',
                'Fecha Fin'         => $endDate ? $endDate->format('d/m/Y') : '—',
                'Días Restantes'    => (string) $daysRemaining,
                'Contacto Principal' => $contactName ?: '—',
                'Email Contacto'    => $row['contact_email'] ?? '—',
                'Teléfono Contacto' => $row['contact_phone'] ?? '—',
                'Estado'            => self::STATUS_LABELS[$row['status']] ?? $row['status'] ?? '—',
            ];
        }

        return $result;
    }
}
