<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\UserMfaBackupCode;
use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineUserMfaBackupCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DoctrineUserMfaBackupCodeRepositoryTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testFindUnusedByUserReturnsArray(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $result = $repo->findUnusedByUser(new UserIdValueObject(self::USER_ID));

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testFindByIdReturnsBackupCode(): void
    {
        $id = new UserMfaBackupCodeIdValueObject('01HZ0000000000000000000001');

        $backupCode = UserMfaBackupCode::generate(
            $id,
            new UserIdValueObject(self::USER_ID),
            'ABCD-EFGH-IJK',
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(UserMfaBackupCode::class, $id)
            ->willReturn($backupCode);

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $result = $repo->findById($id);

        self::assertSame($backupCode, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $id = new UserMfaBackupCodeIdValueObject('01HZ0000000000000000000001');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(UserMfaBackupCode::class, $id)
            ->willReturn(null);

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $result = $repo->findById($id);

        self::assertNull($result);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $backupCode = UserMfaBackupCode::generate(
            new UserMfaBackupCodeIdValueObject('01HZ0000000000000000000001'),
            new UserIdValueObject(self::USER_ID),
            'ABCD-EFGH-IJK',
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($backupCode);
        $em->expects($this->once())->method('flush');

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $repo->save($backupCode);
    }

    public function testMarkAsUsedExecutesUpdateQuery(): void
    {
        $id = new UserMfaBackupCodeIdValueObject('01HZ0000000000000000000001');

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('update')->willReturnSelf();
        $qb->method('set')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $repo->markAsUsed($id);

        self::assertTrue(true);
    }

    public function testCountUnusedByUserReturnsInt(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(5);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $count = $repo->countUnusedByUser(new UserIdValueObject(self::USER_ID));

        self::assertSame(5, $count);
    }

    public function testDeleteAllByUserExecutesDeleteQuery(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(10);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineUserMfaBackupCodeRepository($em);
        $repo->deleteAllByUser(new UserIdValueObject(self::USER_ID));

        self::assertTrue(true);
    }
}
