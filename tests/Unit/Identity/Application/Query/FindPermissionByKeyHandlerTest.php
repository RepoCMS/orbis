<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Query\FindPermissionByKey\FindPermissionByKeyHandler;
use App\Identity\Application\Query\FindPermissionByKey\FindPermissionByKeyQuery;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class FindPermissionByKeyHandlerTest extends TestCase
{
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testFindsPermissionByKey(): void
    {
        $permission = Permission::define(
            new PermissionIdValueObject(self::PERMISSION_ID),
            new PermissionKeyValueObject('role.create'),
            'Create roles',
        );

        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findByKey')->willReturn($permission);

        $handler = new FindPermissionByKeyHandler($repo);
        $response = ($handler)(new FindPermissionByKeyQuery('role.create'));

        self::assertSame('role.create', $response->key);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findByKey')->willReturn(null);

        $handler = new FindPermissionByKeyHandler($repo);
        $this->expectException(PermissionNotFoundException::class);
        ($handler)(new FindPermissionByKeyQuery('role.unknown'));
    }
}
