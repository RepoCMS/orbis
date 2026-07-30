<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Query\FindPermissionById\FindPermissionByIdHandler;
use App\Identity\Application\Query\FindPermissionById\FindPermissionByIdQuery;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class FindPermissionByIdHandlerTest extends TestCase
{
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testFindsPermissionById(): void
    {
        $permission = Permission::define(
            new PermissionIdValueObject(self::PERMISSION_ID),
            new PermissionKeyValueObject('role.create'),
            'Create roles',
        );

        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findById')->willReturn($permission);

        $handler = new FindPermissionByIdHandler($repo);
        $response = ($handler)(new FindPermissionByIdQuery(self::PERMISSION_ID));

        self::assertSame(self::PERMISSION_ID, $response->id);
        self::assertSame('role.create', $response->key);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindPermissionByIdHandler($repo);
        $this->expectException(PermissionNotFoundException::class);
        ($handler)(new FindPermissionByIdQuery(self::PERMISSION_ID));
    }
}
