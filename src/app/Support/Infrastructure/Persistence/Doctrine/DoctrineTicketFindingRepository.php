<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\Repositories\TicketFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketFindingRepository implements TicketFindingRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketFindingIdValueObject $id): ?TicketFinding
    {
        return $this->entityManager->getRepository(TicketFinding::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): ?TicketFinding
    {
        return $this->entityManager->getRepository(TicketFinding::class)
            ->findOneBy(['ticketId' => (string) $ticketId]);
    }

    public function save(TicketFinding $finding): void
    {
        $this->entityManager->persist($finding);
        $this->entityManager->flush();
    }
}
