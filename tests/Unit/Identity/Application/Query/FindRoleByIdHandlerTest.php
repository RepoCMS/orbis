<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\FindRoleById\FindRoleByIdHandler;
use App\Identity\Application\Query\FindRoleById\FindRoleByIdQuery;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class FindRoleByIdHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testFindsRoleById(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn($role);

        $handler = new FindRoleByIdHandler($repo);
        $response = ($handler)(new FindRoleByIdQuery(self::ROLE_ID));

        self::assertSame(self::ROLE_ID, $response->id);
        self::assertSame('Admin', $response->name);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindRoleByIdHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new FindRoleByIdQuery(self::ROLE_ID));
    }
}
