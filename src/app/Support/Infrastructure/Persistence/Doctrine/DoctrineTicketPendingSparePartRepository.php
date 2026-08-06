<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketPendingSparePartRepository implements TicketPendingSparePartRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketPendingSparePartIdValueObject $id): ?TicketPendingSparePart
    {
        return $this->entityManager->getRepository(TicketPendingSparePart::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketPendingSparePart::class)
            ->findBy(['ticketId' => (string) $ticketId]);
    }

    public function save(TicketPendingSparePart $pendingSparePart): void
    {
        $this->entityManager->persist($pendingSparePart);
        $this->entityManager->flush();
    }

    public function delete(TicketPendingSparePartIdValueObject $id): void
    {
        $psp = $this->findById($id);
        if ($psp === null) {
            return;
        }
        $this->entityManager->remove($psp);
        $this->entityManager->flush();
    }
}
