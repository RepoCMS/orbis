<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ApproveTicket;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ApproveTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface     $ticketRepo,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(ApproveTicketCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        // Generate CAT code: {clientCode}-{cityCode}-{terminalCode}-{YYYYMMDD}-{seq}
        $date   = (new \DateTimeImmutable())->format('Ymd');
        $seq    = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $catCode = strtoupper(sprintf(
            '%s-%s-%s-%s-%s',
            $command->clientCode,
            $command->cityCode,
            $command->terminalCode,
            $date,
            $seq
        ));

        $ticket->approve($command->approvedBy, $catCode);

        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
