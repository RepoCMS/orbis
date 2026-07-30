<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAuthLogRepository implements AuthLogRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function append(AuthLog $authLog): void
    {
        $this->em->persist($authLog);
        $this->em->flush();
    }

    public function findByUserId(UserIdValueObject $userId, int $limit, int $offset): array
    {
        return $this->em->createQueryBuilder()
            ->select('a')
            ->from(AuthLog::class, 'a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByUserId(UserIdValueObject $userId): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AuthLog::class, 'a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByFilters(?UserIdValueObject $userId, ?DateTimeImmutable $dateFrom, ?DateTimeImmutable $dateTo, int $page, int $limit): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(AuthLog::class, 'a');

        if ($userId !== null) {
            $qb->andWhere('a.userId = :userId')
               ->setParameter('userId', $userId);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('a.occurredAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('a.occurredAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return $qb
            ->orderBy('a.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->getQuery()
            ->getResult();
    }

    public function countRecentFailedByIp(string $ipAddress, int $minutes): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AuthLog::class, 'a')
            ->where('a.ipAddress = :ip')
            ->andWhere('a.event = :event')
            ->andWhere('a.occurredAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('event', \App\Identity\Domain\Enums\AuthEvent::LOGIN_FAILED->value)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByFilters(?UserIdValueObject $userId, ?DateTimeImmutable $dateFrom, ?DateTimeImmutable $dateTo): int
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AuthLog::class, 'a');

        if ($userId !== null) {
            $qb->andWhere('a.userId = :userId')
               ->setParameter('userId', $userId);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('a.occurredAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('a.occurredAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
