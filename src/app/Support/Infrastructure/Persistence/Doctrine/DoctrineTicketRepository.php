<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketRepository implements TicketRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketIdValueObject $id): ?Ticket
    {
        return $this->entityManager->getRepository(Ticket::class)->find((string) $id);
    }

    public function findByTicketNumber(string $ticketNumber): ?Ticket
    {
        return $this->entityManager->getRepository(Ticket::class)
            ->findOneBy(['ticketNumber' => $ticketNumber]);
    }

    /** @param TicketStatus[] $statuses */
    public function findByStatuses(array $statuses): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(Ticket::class, 't')
            ->where('t.status IN (:statuses)')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('statuses', array_map(fn (TicketStatus $s) => $s->value, $statuses))
            ->orderBy('t.scheduledAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findByClientId(string $clientId): array
    {
        return $this->entityManager->getRepository(Ticket::class)
            ->findBy(['clientId' => $clientId, 'deletedAt' => null], ['scheduledAt' => 'DESC']);
    }

    public function findByEmployeeId(string $employeeId): array
    {
        // Tickets where the employee has an assignment
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT t')
            ->from(Ticket::class, 't')
            ->join(
                \App\Support\Domain\Entities\TicketAssignment::class,
                'a',
                'WITH',
                'a.ticketId = t.id AND a.employeeId = :employeeId',
            )
            ->where('t.deletedAt IS NULL')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('t.scheduledAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Ticket::class)
            ->findBy(['deletedAt' => null], ['scheduledAt' => 'DESC']);
    }

    public function nextTicketSequence(int $year): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.ticketNumber LIKE :prefix')
            ->setParameter('prefix', "TK-{$year}-%");

        return (int) $qb->getQuery()->getSingleScalarResult() + 1;
    }

    public function save(Ticket $ticket): void
    {
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
    }

    public function delete(TicketIdValueObject $id): void
    {
        $ticket = $this->findById($id);
        if ($ticket === null) {
            return;
        }
        $ticket->softDelete();
        $this->entityManager->flush();
    }
}
