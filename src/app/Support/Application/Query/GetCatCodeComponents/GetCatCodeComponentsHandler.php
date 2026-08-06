<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetCatCodeComponents;

use App\Support\Application\Exception\TicketNotFoundException;
use Doctrine\DBAL\Connection;

final readonly class GetCatCodeComponentsHandler
{
    public function __construct(
        private Connection $dbal,
    ) {
    }

    /**
     * Returns the clientCode, cityCode and terminalCode for the given ticket ID.
     *
     * These codes are used to generate the CAT (Código de Aprobación de Ticket) number.
     *
     * @return array{clientCode: string, cityCode: string, terminalCode: string}
     */
    public function __invoke(GetCatCodeComponentsQuery $query): array
    {
        $row = $this->dbal->fetchAssociative(
            'SELECT
                UPPER(SUBSTRING(c.trade_name, 1, 3))  AS client_code,
                UPPER(SUBSTRING(ci.name, 1, 3))       AS city_code,
                COALESCE(ta.id_assigned_by_client, ta.id) AS terminal_code
             FROM support_tickets tk
             JOIN terminal_assignments ta ON ta.id = tk.terminal_assignment_id
             JOIN sites s ON s.id = ta.site_id
             JOIN terminals t ON t.id = ta.terminal_id
             JOIN clients c ON c.id = t.client_id
             JOIN cities ci ON ci.id = s.city_id
             WHERE tk.id = :ticketId',
            ['ticketId' => $query->ticketId],
        );

        if ($row === false) {
            throw TicketNotFoundException::withId($query->ticketId);
        }

        return [
            'clientCode'   => (string) $row['client_code'],
            'cityCode'     => (string) $row['city_code'],
            'terminalCode' => (string) $row['terminal_code'],
        ];
    }
}
