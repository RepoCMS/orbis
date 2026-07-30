<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\FindRoleBySlug\FindRoleBySlugHandler;
use App\Identity\Application\Query\FindRoleBySlug\FindRoleBySlugQuery;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class FindRoleBySlugHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testFindsRoleBySlug(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn($role);

        $handler = new FindRoleBySlugHandler($repo);
        $response = ($handler)(new FindRoleBySlugQuery('admin'));

        self::assertSame('Admin', $response->name);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn(null);

        $handler = new FindRoleBySlugHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new FindRoleBySlugQuery('unknown'));
    }
}
