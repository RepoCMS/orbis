<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(EmployeeIdValueObject $id): ?Employee
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Employee::class, 'e')
            ->where('e.id = :id')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserId(UserIdValueObject $userId): ?Employee
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Employee::class, 'e')
            ->where('e.userId = :userId')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmail(string $email): ?Employee
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Employee::class, 'e')
            ->where('e.email = :email')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Employee[]
     */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Employee::class, 'e')
            ->where('e.deletedAt IS NULL')
            ->orderBy('e.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Employee[]
     */
    public function findByFilters(
        ?EmployeeProfile $profile = null,
        ?EmployeeStatus $status = null,
        ?string $search = null,
        ?string $serviceZoneId = null,
        int $page = 1,
        ?int $limit = null,
    ): array {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Employee::class, 'e')
            ->where('e.deletedAt IS NULL');

        $this->applyEmployeeFilters($qb, $profile, $status, $search, $serviceZoneId);

        if ($limit !== null) {
            $qb->setMaxResults($limit)
                ->setFirstResult(max(0, ($page - 1) * $limit));
        }

        return $qb->orderBy('e.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByFilters(
        ?EmployeeProfile $profile = null,
        ?EmployeeStatus $status = null,
        ?string $search = null,
        ?string $serviceZoneId = null,
    ): int {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Employee::class, 'e')
            ->where('e.deletedAt IS NULL');

        $this->applyEmployeeFilters($qb, $profile, $status, $search, $serviceZoneId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyEmployeeFilters(
        QueryBuilder $qb,
        ?EmployeeProfile $profile,
        ?EmployeeStatus $status,
        ?string $search,
        ?string $serviceZoneId,
    ): void {
        if ($profile !== null) {
            $qb->andWhere('e.profile = :profile')
                ->setParameter('profile', $profile->value);
        }

        if ($status !== null) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $status->value);
        }

        if ($search !== null) {
            $qb->andWhere('LOWER(CONCAT(e.firstName, \' \', e.lastName)) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($serviceZoneId !== null) {
            $qb->andWhere('e.serviceZoneId = :zone')
                ->setParameter('zone', $serviceZoneId);
        }
    }

    public function save(Employee $employee): void
    {
        $this->em->persist($employee);
        $this->em->flush();
    }

    public function delete(EmployeeIdValueObject $id): void
    {
        $this->em->createQueryBuilder()
            ->update(Employee::class, 'e')
            ->set('e.deletedAt', ':now')
            ->where('e.id = :id')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
