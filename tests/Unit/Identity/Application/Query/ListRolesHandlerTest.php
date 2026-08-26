<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Query\ListRoles\ListRolesHandler;
use App\Identity\Application\Query\ListRoles\ListRolesQuery;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class ListRolesHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testListsAllRoles(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$role]);

        $handler = new ListRolesHandler($repo);
        $result = ($handler)(new ListRolesQuery());

        self::assertCount(1, $result);
        self::assertSame('Admin', $result[0]->name);
    }

    public function testReturnsEmptyArrayWhenNoRoles(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListRolesHandler($repo);
        $result = ($handler)(new ListRolesQuery());

        self::assertEmpty($result);
    }
}
