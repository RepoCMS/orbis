<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketAssignmentRepository implements TicketAssignmentRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function findById(TicketAssignmentIdValueObject $id): ?TicketAssignment
    {
        return $this->entityManager->getRepository(TicketAssignment::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketAssignment::class)
            ->findBy(['ticketId' => (string) $ticketId], ['assignedAt' => 'DESC']);
    }

    public function findByEmployee(string $employeeId): array
    {
        return $this->entityManager->getRepository(TicketAssignment::class)
            ->findBy(['employeeId' => $employeeId], ['assignedAt' => 'DESC']);
    }

    public function save(TicketAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }
}
