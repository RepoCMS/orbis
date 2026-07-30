<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineUserMfaConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DoctrineUserMfaConfigRepositoryTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testFindByUserIdReturnsConfig(): void
    {
        $config = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'JBSWY3DPEHPK3PXP',
        );

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($config);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineUserMfaConfigRepository($em);
        $result = $repo->findByUserId(new UserIdValueObject(self::USER_ID));

        self::assertSame($config, $result);
    }

    public function testFindByUserIdReturnsNullWhenNotFound(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineUserMfaConfigRepository($em);
        $result = $repo->findByUserId(new UserIdValueObject(self::USER_ID));

        self::assertNull($result);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $config = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'JBSWY3DPEHPK3PXP',
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($config);
        $em->expects($this->once())->method('flush');

        $repo = new DoctrineUserMfaConfigRepository($em);
        $repo->save($config);
    }

    public function testDeleteByUserIdRemovesAndFlushes(): void
    {
        $config = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'JBSWY3DPEHPK3PXP',
        );

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($config);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);
        $em->expects($this->once())->method('remove')->with($config);
        $em->expects($this->once())->method('flush');

        $repo = new DoctrineUserMfaConfigRepository($em);
        $repo->deleteByUserId(new UserIdValueObject(self::USER_ID));
    }

    public function testDeleteByUserIdDoesNothingWhenNotFound(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $repo = new DoctrineUserMfaConfigRepository($em);
        $repo->deleteByUserId(new UserIdValueObject(self::USER_ID));
    }
}
