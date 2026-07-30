<?php

declare(strict_types=1);

namespace App\Tests\Functional\Client;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
#[Group('functional-client')]
final class ContractControllerTest extends FunctionalTestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const LIST_ROUTE = '/clients/' . self::CLIENT_ID . '/contracts';
    private const NEW_FORM_ROUTE = '/clients/' . self::CLIENT_ID . '/contracts/new';
    private const SHOW_ROUTE = '/clients/' . self::CLIENT_ID . '/contracts/01ARZ3NDEKTSV4RRFFQ69G5FAV';

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
}
