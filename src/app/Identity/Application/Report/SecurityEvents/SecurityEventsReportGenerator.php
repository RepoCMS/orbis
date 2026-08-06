<?php

declare(strict_types=1);

namespace App\Identity\Application\Report\SecurityEvents;

use App\Shared\Application\Report\ReportGeneratorInterface;
use Doctrine\DBAL\Connection;

final readonly class SecurityEventsReportGenerator implements ReportGeneratorInterface
{
    private const EVENT_LABELS = [
        'login_success'     => 'Inicio de sesión exitoso',
        'login_failed'      => 'Inicio de sesión fallido',
        'logout'            => 'Cierre de sesión',
        'password_change'   => 'Cambio de contraseña',
        'account_locked'    => 'Cuenta bloqueada',
        'account_unlocked'  => 'Cuenta desbloqueada',
        'mfa_enabled'       => 'MFA habilitado',
        'mfa_disabled'      => 'MFA deshabilitado',
        'mfa_challenge'     => 'Desafío MFA',
        'mfa_recovery'      => 'Recuperación MFA',
        'token_refresh'     => 'Actualización de token',
        'user_created'      => 'Usuario creado',
        'user_deactivated'  => 'Usuario desactivado',
        'user_activated'    => 'Usuario activado',
        'role_assigned'     => 'Rol asignado',
        'permission_granted' => 'Permiso concedido',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getTitle(): string
    {
        return 'Historial de Eventos de Seguridad';
    }

    public function getHeaders(): array
    {
        return [
            'Fecha',
            'Usuario',
            'Evento',
            'Dirección IP',
            'User-Agent',
        ];
    }

    public function generate(array $filters = []): array
    {
        $params = [];
        $conditions = [];

        if (!empty($filters['date_from'])) {
            $conditions[] = 'al.occurred_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'al.occurred_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['event'])) {
            $conditions[] = 'al.event = :event';
            $params['event'] = $filters['event'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = 'al.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['ip_address'])) {
            $conditions[] = 'al.ip_address LIKE :ip_address';
            $params['ip_address'] = '%' . $filters['ip_address'] . '%';
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "
            SELECT
                al.user_id,
                al.event,
                al.ip_address,
                al.user_agent,
                al.occurred_at,
                u.username
            FROM identity_auth_logs al
            LEFT JOIN identity_users u ON u.id = al.user_id AND u.deleted_at IS NULL
            {$where}
            ORDER BY al.occurred_at DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'Fecha'          => $row['occurred_at'] ? (new \DateTimeImmutable($row['occurred_at']))->format('d/m/Y H:i:s') : '—',
                'Usuario'        => $row['username'] ?? $row['user_id'] ?? '—',
                'Evento'         => self::EVENT_LABELS[$row['event']] ?? $row['event'] ?? '—',
                'Dirección IP'   => $row['ip_address'] ?? '—',
                'User-Agent'     => $row['user_agent'] ?? '—',
            ];
        }

        return $result;
    }
}
