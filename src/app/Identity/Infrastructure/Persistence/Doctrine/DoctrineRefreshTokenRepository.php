<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(RefreshToken $token): void
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    public function findByHash(string $tokenHash): ?RefreshToken
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(RefreshToken::class, 't')
            ->where('t.tokenHash = :hash')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserId(UserIdValueObject $userId): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(RefreshToken::class, 't')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteExpired(DateTimeImmutable $now): int
    {
        return $this->em->createQueryBuilder()
            ->delete(RefreshToken::class, 't')
            ->where('t.expiresAt < :now OR t.revokedAt IS NOT NULL')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }
}
