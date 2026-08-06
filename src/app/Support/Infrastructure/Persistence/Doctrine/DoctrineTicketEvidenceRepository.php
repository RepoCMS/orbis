<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketEvidence;
use App\Support\Domain\Repositories\TicketEvidenceRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketEvidenceRepository implements TicketEvidenceRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketEvidenceIdValueObject $id): ?TicketEvidence
    {
        return $this->entityManager->getRepository(TicketEvidence::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketEvidence::class)
            ->findBy(['ticketId' => (string) $ticketId]);
    }

    public function save(TicketEvidence $evidence): void
    {
        $this->entityManager->persist($evidence);
        $this->entityManager->flush();
    }

    public function delete(TicketEvidenceIdValueObject $id): void
    {
        $evidence = $this->findById($id);
        if ($evidence === null) {
            return;
        }
        $this->entityManager->remove($evidence);
        $this->entityManager->flush();
    }
}
