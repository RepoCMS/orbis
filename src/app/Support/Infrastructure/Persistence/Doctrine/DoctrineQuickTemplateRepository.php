<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineQuickTemplateRepository implements QuickTemplateRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(QuickTemplateIdValueObject $id): ?QuickTemplate
    {
        return $this->em->find(QuickTemplate::class, $id);
    }

    public function findByModelId(string $modelId): array
    {
        return $this->em->createQueryBuilder()
            ->select('qt')
            ->from(QuickTemplate::class, 'qt')
            ->where('qt.modelId = :modelId')
            ->setParameter('modelId', $modelId)
            ->orderBy('qt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByModelId(string $modelId): array
    {
        return $this->em->createQueryBuilder()
            ->select('qt')
            ->from(QuickTemplate::class, 'qt')
            ->where('qt.modelId = :modelId')
            ->andWhere('qt.isActive = :active')
            ->setParameter('modelId', $modelId)
            ->setParameter('active', true)
            ->orderBy('qt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('qt')
            ->from(QuickTemplate::class, 'qt')
            ->orderBy('qt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(QuickTemplate $template): void
    {
        $this->em->persist($template);
        $this->em->flush();
    }

    public function delete(QuickTemplate $template): void
    {
        $this->em->remove($template);
        $this->em->flush();
    }
}
