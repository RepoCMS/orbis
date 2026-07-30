<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePurchaseOrderItemRepository implements PurchaseOrderItemRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(PurchaseOrderItemIdValueObject $id): ?PurchaseOrderItem
    {
        return $this->em->find(PurchaseOrderItem::class, $id);
    }

    public function findByPurchaseOrder(PurchaseOrderIdValueObject $purchaseOrderId): array
    {
        return $this->em->createQueryBuilder()
            ->select('i')->from(PurchaseOrderItem::class, 'i')
            ->where('i.purchaseOrderId = :poId')
            ->setParameter('poId', $purchaseOrderId)
            ->getQuery()->getResult();
    }

    public function save(PurchaseOrderItem $item): void
    {
        $this->em->persist($item);
        $this->em->flush();
    }

    public function findLastPurchaseDateByProduct(string $productId): ?\DateTimeImmutable
    {
        $conn = $this->em->getConnection();

        $result = $conn->fetchOne(
            'SELECT MAX(po.created_at) FROM purchase_order_items poi
             INNER JOIN purchase_orders po ON po.id = poi.purchase_order_id
             WHERE poi.product_id = :productId',
            ['productId' => $productId],
        );

        if ($result === null || $result === false) {
            return null;
        }

        return new \DateTimeImmutable((string) $result);
    }
}
