<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(PermissionIdValueObject $id): ?Permission
    {
        return $this->em->find(Permission::class, $id);
    }

    public function findByKey(PermissionKeyValueObject $key): ?Permission
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Permission::class, 'p')
            ->where('p.key = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Permission::class, 'p')
            ->orderBy('p.key', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Permission::class, 'p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function save(Permission $permission): void
    {
        $this->em->persist($permission);
        $this->em->flush();
    }

    public function delete(PermissionIdValueObject $id): void
    {
        $permission = $this->em->find(Permission::class, $id);

        if ($permission !== null) {
            $this->em->remove($permission);
            $this->em->flush();
        }
    }
}
