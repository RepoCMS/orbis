<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\UserToken;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserTokenRepository implements UserTokenRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(UserTokenIdValueObject $id): ?UserToken
    {
        return $this->em->find(UserToken::class, $id);
    }

    public function findByHash(string $tokenHash): ?UserToken
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(UserToken::class, 't')
            ->where('t.tokenHash = :hash')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserAndType(UserIdValueObject $userId, UserTokenType $type): ?UserToken
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(UserToken::class, 't')
            ->where('t.userId = :userId AND t.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(UserToken $token): void
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    public function delete(UserTokenIdValueObject $id): void
    {
        $token = $this->em->find(UserToken::class, $id);

        if ($token !== null) {
            $this->em->remove($token);
            $this->em->flush();
        }
    }

    public function deleteByUserAndType(UserIdValueObject $userId, UserTokenType $type): void
    {
        $this->em->createQueryBuilder()
            ->delete(UserToken::class, 't')
            ->where('t.userId = :userId AND t.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type->value)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): void
    {
        $this->em->createQueryBuilder()
            ->delete(UserToken::class, 't')
            ->where('t.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
