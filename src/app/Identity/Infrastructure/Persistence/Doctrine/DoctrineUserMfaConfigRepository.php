<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserMfaConfigRepository implements UserMfaConfigRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findByUserId(UserIdValueObject $userId): ?UserMfaConfig
    {
        return $this->em->createQueryBuilder()
            ->select('m')
            ->from(UserMfaConfig::class, 'm')
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(UserMfaConfig $config): void
    {
        $this->em->persist($config);
        $this->em->flush();
    }

    public function deleteByUserId(UserIdValueObject $userId): void
    {
        $config = $this->findByUserId($userId);

        if ($config !== null) {
            $this->em->remove($config);
            $this->em->flush();
        }
    }
}
