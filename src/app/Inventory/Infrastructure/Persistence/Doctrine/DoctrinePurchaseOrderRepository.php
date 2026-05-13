<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(PurchaseOrderIdValueObject $id): ?PurchaseOrder
    {
        return $this->em->find(PurchaseOrder::class, $id);
    }

    public function findByReference(string $reference): ?PurchaseOrder
    {
        return $this->em->createQueryBuilder()
            ->select('p')->from(PurchaseOrder::class, 'p')
            ->where('p.reference = :ref')
            ->setParameter('ref', $reference)
            ->getQuery()->getOneOrNullResult();
    }

    public function findOpen(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')->from(PurchaseOrder::class, 'p')
            ->where('p.status IN (:statuses)')
            ->setParameter('statuses', [PurchaseOrderStatus::OPEN->value, PurchaseOrderStatus::PARTIAL->value])
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')->from(PurchaseOrder::class, 'p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function save(PurchaseOrder $purchaseOrder): void
    {
        $this->em->persist($purchaseOrder);
        $this->em->flush();
    }
}
