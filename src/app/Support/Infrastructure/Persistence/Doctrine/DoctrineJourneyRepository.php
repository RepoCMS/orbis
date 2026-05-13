<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Repositories\JourneyRepositoryInterface;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineJourneyRepository implements JourneyRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function findById(JourneyIdValueObject $id): ?Journey
    {
        return $this->entityManager->getRepository(Journey::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(Journey::class)
            ->findBy(['ticketId' => (string) $ticketId], ['startedAt' => 'ASC']);
    }

    public function save(Journey $journey): void
    {
        $this->entityManager->persist($journey);
        $this->entityManager->flush();
    }
}
