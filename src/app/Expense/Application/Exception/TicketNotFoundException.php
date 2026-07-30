<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class TicketNotFoundException extends ApplicationException
{
    public function __construct(string $ticketId)
    {
        parent::__construct(
            sprintf('Ticket no encontrado: %s', $ticketId),
        );
    }
}
