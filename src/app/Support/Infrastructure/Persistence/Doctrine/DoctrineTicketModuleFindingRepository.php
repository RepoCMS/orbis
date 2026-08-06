<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketModuleFindingRepository implements TicketModuleFindingRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketModuleFindingIdValueObject $id): ?TicketModuleFinding
    {
        return $this->entityManager->getRepository(TicketModuleFinding::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketModuleFinding::class)
            ->findBy(['ticketId' => (string) $ticketId]);
    }

    public function save(TicketModuleFinding $finding): void
    {
        $this->entityManager->persist($finding);
        $this->entityManager->flush();
    }

    public function delete(TicketModuleFinding $finding): void
    {
        $this->entityManager->remove($finding);
        $this->entityManager->flush();
    }
}
