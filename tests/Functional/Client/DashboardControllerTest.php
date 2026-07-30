<?php

declare(strict_types=1);

namespace App\Tests\Functional\Client;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
#[Group('functional-client')]
final class DashboardControllerTest extends FunctionalTestCase
{
    private const ROUTE = '/client/dashboard';

    public function testRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ROUTE);
        self::assertRouteExists($client->getResponse());
    }

    public function testDashboardRedirectsWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ROUTE);
        self::assertResponseIsRedirectToLogin($client->getResponse());
    }

    public function testDashboardReturnsHtmlContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ROUTE);
        $response = $client->getResponse();

        $contentType = $response->headers->get('Content-Type') ?? '';
        self::assertStringContainsString('text/html', $contentType,
            \sprintf('Expected HTML content type, got: %s', $contentType));
    }
}
