<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineAuthLogRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DoctrineAuthLogRepositoryTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAppendPersistsAndFlushes(): void
    {
        $authLog = AuthLog::record(
            new AuthLogIdValueObject('01HZ0000000000000000000001'),
            new UserIdValueObject(self::USER_ID),
            AuthEvent::LOGIN_SUCCESS,
            '127.0.0.1',
            'Mozilla/5.0',
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($authLog);
        $em->expects($this->once())->method('flush');

        $repo = new DoctrineAuthLogRepository($em);
        $repo->append($authLog);
    }

    public function testFindByUserIdReturnsArray(): void
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
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineAuthLogRepository($em);
        $result = $repo->findByUserId(new UserIdValueObject(self::USER_ID), 10, 0);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCountByUserIdReturnsInt(): void
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

        $repo = new DoctrineAuthLogRepository($em);
        $count = $repo->countByUserId(new UserIdValueObject(self::USER_ID));

        self::assertSame(5, $count);
    }

    public function testFindByFiltersWithUserFilterAndPagination(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineAuthLogRepository($em);
        $result = $repo->findByFilters(
            new UserIdValueObject(self::USER_ID),
            new DateTimeImmutable('-7 days'),
            new DateTimeImmutable(),
            1,
            10,
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testFindByFiltersWithoutUserReturnsAll(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineAuthLogRepository($em);
        $result = $repo->findByFilters(null, null, null, 1, 10);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCountByFiltersReturnsTotal(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(42);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineAuthLogRepository($em);
        $count = $repo->countByFilters(
            new UserIdValueObject(self::USER_ID),
            new DateTimeImmutable('-30 days'),
            new DateTimeImmutable(),
        );

        self::assertSame(42, $count);
    }

    public function testCountRecentFailedByIpReturnsCount(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(3);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineAuthLogRepository($em);
        $count = $repo->countRecentFailedByIp('192.168.1.1', 15);

        self::assertSame(3, $count);
    }
}
