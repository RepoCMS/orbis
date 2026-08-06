<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Persistence\Doctrine;

use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Expense\Domain\Repositories\ExpenseItemRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineExpenseItemRepository implements ExpenseItemRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findById(ExpenseItemIdValueObject $id): ?ExpenseItem
    {
        $item = $this->em->find(ExpenseItem::class, $id);
        if ($item !== null && $item->isDeleted()) {
            return null;
        }

        return $item;
    }

    public function findByExpenseId(string $expenseId): array
    {
        return $this->em->createQueryBuilder()
            ->select('i')
            ->from(ExpenseItem::class, 'i')
            ->where('i.expense = :expenseId')
            ->andWhere('i.deletedAt IS NULL')
            ->setParameter('expenseId', $expenseId)
            ->orderBy('i.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingByExpenseId(string $expenseId): array
    {
        return $this->em->createQueryBuilder()
            ->select('i')
            ->from(ExpenseItem::class, 'i')
            ->where('i.expense = :expenseId')
            ->andWhere('i.status = :status')
            ->andWhere('i.deletedAt IS NULL')
            ->setParameter('expenseId', $expenseId)
            ->setParameter('status', ExpenseItemStatus::PENDING->value)
            ->orderBy('i.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ExpenseItem $item): void
    {
        $this->em->persist($item);
        $this->em->flush();
    }
}
