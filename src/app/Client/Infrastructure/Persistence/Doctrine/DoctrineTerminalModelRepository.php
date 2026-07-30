<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\TerminalModel;
use App\Client\Domain\Repositories\TerminalModelRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTerminalModelRepository implements TerminalModelRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(TerminalModelIdValueObject $id): ?TerminalModel
    {
        $model = $this->em->find(TerminalModel::class, $id);

        if ($model !== null && $model->isDeleted()) {
            return null;
        }

        return $model;
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('tm')
            ->from(TerminalModel::class, 'tm')
            ->where('tm.deletedAt IS NULL')
            ->orderBy('tm.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(TerminalModel $model): void
    {
        $this->em->persist($model);
        $this->em->flush();
    }

    public function delete(TerminalModelIdValueObject $id): void
    {
        $model = $this->em->find(TerminalModel::class, $id);

        if ($model !== null) {
            $model->softDelete();
            $this->save($model);
        }
    }
}
