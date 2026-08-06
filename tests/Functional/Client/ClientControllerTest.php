<?php

declare(strict_types=1);

namespace App\Tests\Functional\Client;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
#[Group('functional-client')]
final class ClientControllerTest extends FunctionalTestCase
{
    private const LIST_ROUTE = '/clients';
    private const NEW_FORM_ROUTE = '/clients/new';
    private const SHOW_ROUTE = '/clients/01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const DASHBOARD_ROUTE = '/client/dashboard';
    private const IMPORT_ROUTE = '/client/import';

    public function testListRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LIST_ROUTE);
        self::assertRouteExists($client->getResponse());
    }

    public function testListRedirectsWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LIST_ROUTE);
        self::assertResponseIsRedirectToLogin($client->getResponse());
    }

    public function testNewFormRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', self::NEW_FORM_ROUTE);
        self::assertRouteExists($client->getResponse());
    }

    public function testNewFormRedirectsWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', self::NEW_FORM_ROUTE);
        self::assertResponseIsRedirectToLogin($client->getResponse());
    }

    public function testShowRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', self::SHOW_ROUTE);
        self::assertRouteExists($client->getResponse());
    }

    public function testShowRedirectsWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', self::SHOW_ROUTE);
        self::assertResponseIsRedirectToLogin($client->getResponse());
    }

    public function testDashboardRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', self::DASHBOARD_ROUTE);
        self::assertRouteExists($client->getResponse());
    }

    public function testDashboardRedirectsWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', self::DASHBOARD_ROUTE);
        self::assertResponseIsRedirectToLogin($client->getResponse());
    }

    public function testImportRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', self::IMPORT_ROUTE);
        self::assertRouteExists($client->getResponse());
    }

    public function testImportRedirectsWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', self::IMPORT_ROUTE);
        self::assertResponseIsRedirectToLogin($client->getResponse());
    }

    public function testInvalidRouteReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients/nonexistent/route/that/does/not/exist');
        self::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testListReturnsHtmlContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LIST_ROUTE);
        $response = $client->getResponse();

        $contentType = $response->headers->get('Content-Type') ?? '';
        self::assertStringContainsString(
            'text/html',
            $contentType,
            \sprintf('Expected HTML content type, got: %s', $contentType)
        );
    }
}
