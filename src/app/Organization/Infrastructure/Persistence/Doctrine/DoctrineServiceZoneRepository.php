<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineServiceZoneRepository implements ServiceZoneRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(ServiceZoneIdValueObject $id): ?ServiceZone
    {
        return $this->em->createQueryBuilder()
            ->select('z')
            ->from(ServiceZone::class, 'z')
            ->where('z.id = :id')
            ->andWhere('z.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return ServiceZone[] */
    public function findAllActive(): array
    {
        return $this->em->createQueryBuilder()
            ->select('z')
            ->from(ServiceZone::class, 'z')
            ->where('z.deletedAt IS NULL')
            ->andWhere("z.status = 'active'")
            ->orderBy('z.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ServiceZone[] */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('z')
            ->from(ServiceZone::class, 'z')
            ->where('z.deletedAt IS NULL')
            ->orderBy('z.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function existsByName(string $name, ?ServiceZoneIdValueObject $excludeId = null): bool
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(z.id)')
            ->from(ServiceZone::class, 'z')
            ->where('z.name = :name')
            ->andWhere('z.deletedAt IS NULL')
            ->setParameter('name', $name);

        if ($excludeId !== null) {
            $qb->andWhere('z.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function save(ServiceZone $zone): void
    {
        $this->em->persist($zone);
        $this->em->flush();
    }
}
