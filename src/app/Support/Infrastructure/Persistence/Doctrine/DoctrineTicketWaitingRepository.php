<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketWaiting;
use App\Support\Domain\Repositories\TicketWaitingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketWaitingIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketWaitingRepository implements TicketWaitingRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(TicketWaitingIdValueObject $id): ?TicketWaiting
    {
        return $this->em->find(TicketWaiting::class, $id);
    }

    public function findByTicketId(string $ticketId): array
    {
        return $this->em->createQueryBuilder()
            ->select('tw')
            ->from(TicketWaiting::class, 'tw')
            ->where('tw.ticketId = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('tw.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TicketWaiting $waiting): void
    {
        $this->em->persist($waiting);
        $this->em->flush();
    }
}
