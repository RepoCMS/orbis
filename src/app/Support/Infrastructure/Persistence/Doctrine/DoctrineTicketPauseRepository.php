<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketPause;
use App\Support\Domain\Repositories\TicketPauseRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketPauseRepository implements TicketPauseRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketPauseIdValueObject $id): ?TicketPause
    {
        return $this->entityManager->getRepository(TicketPause::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketPause::class)
            ->findBy(['ticketId' => (string) $ticketId], ['pausedAt' => 'ASC']);
    }

    public function save(TicketPause $pause): void
    {
        $this->entityManager->persist($pause);
        $this->entityManager->flush();
    }
}
