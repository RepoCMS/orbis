<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineRefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DoctrineRefreshTokenRepositoryTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testSavePersistsAndFlushesToken(): void
    {
        $token = RefreshToken::create(
            RefreshTokenIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB1'),
            new UserIdValueObject(self::USER_ID),
            bin2hex(random_bytes(32)),
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($token);
        $em->expects($this->once())->method('flush');

        $repo = new DoctrineRefreshTokenRepository($em);
        $repo->save($token);
    }

    public function testFindByHashDelegatesQueryCorrectly(): void
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

        $repo = new DoctrineRefreshTokenRepository($em);

        $result = $repo->findByHash(hash('sha256', 'test-token'));

        self::assertNull($result);
    }

    public function testDeleteExpiredDeletesExpiredAndRevokedTokens(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(3);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineRefreshTokenRepository($em);

        $deleted = $repo->deleteExpired(new DateTimeImmutable());

        self::assertSame(3, $deleted);
    }

    public function testFindByUserIdQueriesWithUserId(): void
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
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $repo = new DoctrineRefreshTokenRepository($em);

        $result = $repo->findByUserId(new UserIdValueObject(self::USER_ID));

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
