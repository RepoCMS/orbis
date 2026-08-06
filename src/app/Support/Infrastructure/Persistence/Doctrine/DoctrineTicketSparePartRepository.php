<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketSparePart;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketSparePartRepository implements TicketSparePartRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketSparePartIdValueObject $id): ?TicketSparePart
    {
        return $this->entityManager->getRepository(TicketSparePart::class)->find((string) $id);
    }

    public function findByTicket(TicketIdValueObject $ticketId): array
    {
        return $this->entityManager->getRepository(TicketSparePart::class)
            ->findBy(['ticketId' => (string) $ticketId]);
    }

    public function findByFinding(TicketFindingIdValueObject $findingId): array
    {
        return $this->entityManager->getRepository(TicketSparePart::class)
            ->findBy(['findingId' => (string) $findingId]);
    }

    public function findByEmployeeId(string $employeeId): array
    {
        $dql = 'SELECT sp FROM ' . TicketSparePart::class . ' sp
                JOIN App\Support\Domain\Entities\TicketAssignment ta
                WITH sp.ticketId = ta.ticketId
                WHERE ta.employeeId = :employeeId';

        return $this->entityManager->createQuery($dql)
            ->setParameter('employeeId', $employeeId)
            ->getResult();
    }

    public function save(TicketSparePart $sparePart): void
    {
        $this->entityManager->persist($sparePart);
        $this->entityManager->flush();
    }

    public function delete(TicketSparePartIdValueObject $id): void
    {
        $sp = $this->findById($id);
        if ($sp === null) {
            return;
        }
        $this->entityManager->remove($sp);
        $this->entityManager->flush();
    }
}
