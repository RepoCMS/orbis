<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\FindRoleByName\FindRoleByNameHandler;
use App\Identity\Application\Query\FindRoleByName\FindRoleByNameQuery;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class FindRoleByNameHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testFindsRoleByName(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findByName')->willReturn($role);

        $handler = new FindRoleByNameHandler($repo);
        $response = ($handler)(new FindRoleByNameQuery('Admin'));

        self::assertSame('Admin', $response->name);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findByName')->willReturn(null);

        $handler = new FindRoleByNameHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new FindRoleByNameQuery('Unknown'));
    }
}
