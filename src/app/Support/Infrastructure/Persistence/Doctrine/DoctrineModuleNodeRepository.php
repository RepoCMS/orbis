<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineModuleNodeRepository implements ModuleNodeRepositoryInterface
{
    private EntityRepository $repo;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(ModuleNode::class);
    }

    public function findById(ModuleNodeIdValueObject $id): ?ModuleNode
    {
        return $this->repo->find($id);
    }

    public function findByCodeAndModel(string $code, AtmModel $model): ?ModuleNode
    {
        return $this->repo->findOneBy([
            'code' => $code,
            'model' => $model->value,
        ]);
    }

    public function findByModel(AtmModel $model): array
    {
        return $this->repo->findBy(
            ['model' => $model->value],
            ['position' => 'ASC'],
        );
    }

    public function findRootsByModel(AtmModel $model): array
    {
        return $this->repo->findBy(
            ['model' => $model->value, 'parent' => null],
            ['position' => 'ASC'],
        );
    }

    public function findChildrenByParentId(ModuleNodeIdValueObject $parentId): array
    {
        return $this->repo->findBy(
            ['parent' => $parentId],
            ['position' => 'ASC'],
        );
    }

    public function getNextPosition(?ModuleNodeIdValueObject $parentId, AtmModel $model): int
    {
        $qb = $this->repo->createQueryBuilder('n');
        $maxPosition = $qb->select('COALESCE(MAX(n.position), -1)')
            ->where('n.model = :model')
            ->setParameter('model', $model->value);

        if ($parentId !== null) {
            $qb->andWhere('n.parent = :parent')
                ->setParameter('parent', (string) $parentId);
        } else {
            $qb->andWhere('n.parent IS NULL');
        }

        return (int) $maxPosition->getQuery()->getSingleScalarResult() + 1;
    }

    public function save(ModuleNode $node): void
    {
        $this->em->persist($node);
        $this->em->flush();
    }

    public function delete(ModuleNode $node): void
    {
        $this->em->remove($node);
        $this->em->flush();
    }
}
