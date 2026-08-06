<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Enums\VacationRequestStatus;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineVacationRequestRepository implements VacationRequestRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(VacationRequest $request): void
    {
        $this->em->persist($request);
        $this->em->flush();
    }

    public function findById(VacationRequestIdValueObject $id): ?VacationRequest
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(VacationRequest::class, 'r')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return VacationRequest[]
     */
    public function findPendingByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(VacationRequest::class, 'r')
            ->where('r.employeeId = :employeeId')
            ->andWhere('r.status = :status')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('status', VacationRequestStatus::PENDING->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return VacationRequest[]
     */
    public function findAllByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(VacationRequest::class, 'r')
            ->where('r.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
