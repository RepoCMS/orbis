<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Organization\Domain\Entities\ServiceZoneAssignment;
use App\Organization\Domain\Repositories\ServiceZoneAssignmentRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineServiceZoneAssignmentRepository implements ServiceZoneAssignmentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(ServiceZoneAssignment $assignment): void
    {
        $this->em->persist($assignment);
        $this->em->flush();
    }

    public function findById(ServiceZoneAssignmentIdValueObject $id): ?ServiceZoneAssignment
    {
        return $this->em->createQueryBuilder()
            ->select('a')
            ->from(ServiceZoneAssignment::class, 'a')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ServiceZoneAssignment[]
     */
    public function findByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('a')
            ->from(ServiceZoneAssignment::class, 'a')
            ->where('a.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
