<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketReschedule;
use App\Support\Domain\Repositories\TicketRescheduleRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketRescheduleRepository implements TicketRescheduleRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function findById(TicketRescheduleIdValueObject $id): ?TicketReschedule
    {
        return $this->entityManager->getRepository(TicketReschedule::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketReschedule::class)
            ->findBy(['ticketId' => (string) $ticketId], ['rescheduledAt' => 'DESC']);
    }

    public function save(TicketReschedule $reschedule): void
    {
        $this->entityManager->persist($reschedule);
        $this->entityManager->flush();
    }
}
