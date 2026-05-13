<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\Site;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSiteRepository implements SiteRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(SiteIdValueObject $id): ?Site
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(Site::class, 's')
            ->where('s.id = :id')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByClientId(ClientIdValueObject $clientId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(Site::class, 's')
            ->where('s.clientId = :clientId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('clientId', $clientId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(Site::class, 's')
            ->where('s.deletedAt IS NULL')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Site $site): void
    {
        $this->em->persist($site);
        $this->em->flush();
    }
}
