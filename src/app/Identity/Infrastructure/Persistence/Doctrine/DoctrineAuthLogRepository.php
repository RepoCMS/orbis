<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
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
}
