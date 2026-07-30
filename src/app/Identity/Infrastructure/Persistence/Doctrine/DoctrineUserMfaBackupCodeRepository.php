<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\UserMfaBackupCode;
use App\Identity\Domain\Repositories\UserMfaBackupCodeRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserMfaBackupCodeRepository implements UserMfaBackupCodeRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return UserMfaBackupCode[]
     */
    public function findUnusedByUser(UserIdValueObject $userId): array
    {
        return $this->em->createQueryBuilder()
            ->select('b')
            ->from(UserMfaBackupCode::class, 'b')
            ->where('b.userId = :userId AND b.usedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findById(UserMfaBackupCodeIdValueObject $id): ?UserMfaBackupCode
    {
        return $this->em->find(UserMfaBackupCode::class, $id);
    }

    public function findByCode(UserIdValueObject $userId, string $code): ?UserMfaBackupCode
    {
        return $this->em->createQueryBuilder()
            ->select('b')
            ->from(UserMfaBackupCode::class, 'b')
            ->where('b.userId = :userId AND b.code = :code AND b.usedAt IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(UserMfaBackupCode $backupCode): void
    {
        $this->em->persist($backupCode);
        $this->em->flush();
    }

    public function markAsUsed(UserMfaBackupCodeIdValueObject $id): void
    {
        $this->em->createQueryBuilder()
            ->update(UserMfaBackupCode::class, 'b')
            ->set('b.usedAt', ':now')
            ->where('b.id = :id')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function countUnusedByUser(UserIdValueObject $userId): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(UserMfaBackupCode::class, 'b')
            ->where('b.userId = :userId AND b.usedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteAllByUser(UserIdValueObject $userId): void
    {
        $this->em->createQueryBuilder()
            ->delete(UserMfaBackupCode::class, 'b')
            ->where('b.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
