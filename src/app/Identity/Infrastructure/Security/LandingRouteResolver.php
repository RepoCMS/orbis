<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

final class LandingRouteResolver
{
    /**
     * Ordered by sidebar priority.
     *
     * @var array<string, string>
     */
    private const ROUTES_BY_PERMISSION = [
        'user.view_all' => 'user_list',
        'role.view_all' => 'role_list',
        'client.view_all' => 'client_list',
        'employee.view_all' => 'employee_list',
    ];

    public function resolveFor(?SymfonySecurityUser $user): string
    {
        if ($user === null) {
            return 'app_login';
        }

        return 'app_dashboard';
    }
}