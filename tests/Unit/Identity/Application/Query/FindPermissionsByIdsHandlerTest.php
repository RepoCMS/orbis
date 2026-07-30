<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Query\FindPermissionsByIds\FindPermissionsByIdsHandler;
use App\Identity\Application\Query\FindPermissionsByIds\FindPermissionsByIdsQuery;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class FindPermissionsByIdsHandlerTest extends TestCase
{
    private const PERMISSION_A = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const PERMISSION_B = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testFindsPermissionsByIds(): void
    {
        $permA = Permission::define(
            new PermissionIdValueObject(self::PERMISSION_A),
            new PermissionKeyValueObject('role.create'),
            'Create roles',
        );
        $permB = Permission::define(
            new PermissionIdValueObject(self::PERMISSION_B),
            new PermissionKeyValueObject('role.delete'),
            'Delete roles',
        );

        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findByIds')->willReturn([$permA, $permB]);

        $handler = new FindPermissionsByIdsHandler($repo);
        $result = ($handler)(new FindPermissionsByIdsQuery([self::PERMISSION_A, self::PERMISSION_B]));

        self::assertCount(2, $result);
        self::assertSame('role.create', $result[0]->key);
        self::assertSame('role.delete', $result[1]->key);
    }

    public function testReturnsEmptyArrayWhenNoneFound(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findByIds')->willReturn([]);

        $handler = new FindPermissionsByIdsHandler($repo);
        $result = ($handler)(new FindPermissionsByIdsQuery([self::PERMISSION_A]));

        self::assertEmpty($result);
    }
}
