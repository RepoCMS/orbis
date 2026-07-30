<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Query\ListPermissions\ListPermissionsHandler;
use App\Identity\Application\Query\ListPermissions\ListPermissionsQuery;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class ListPermissionsHandlerTest extends TestCase
{
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testListsAllPermissions(): void
    {
        $permission = Permission::define(
            new PermissionIdValueObject(self::PERMISSION_ID),
            new PermissionKeyValueObject('role.create'),
            'Create roles',
        );

        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$permission]);

        $handler = new ListPermissionsHandler($repo);
        $result = ($handler)(new ListPermissionsQuery());

        self::assertCount(1, $result);
        self::assertSame('role.create', $result[0]->key);
    }

    public function testReturnsEmptyArrayWhenNoPermissions(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListPermissionsHandler($repo);
        $result = ($handler)(new ListPermissionsQuery());

        self::assertEmpty($result);
    }
}
