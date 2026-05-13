<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

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
