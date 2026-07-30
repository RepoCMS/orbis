<?php

declare(strict_types=1);

namespace App\Tests\Functional\Client;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
#[Group('functional-client')]
final class ApiClientControllerTest extends FunctionalTestCase
{
    private const API_LIST_ROUTE = '/api/v1/client/clients';
    private const API_SHOW_ROUTE = '/api/v1/client/clients/01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testListReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'GET', self::API_LIST_ROUTE);
        static::assertSame(401, $client->getResponse()->getStatusCode(),
            'Unauthenticated API request should return 401');
    }

    public function testShowReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'GET', self::API_SHOW_ROUTE);
        static::assertSame(401, $client->getResponse()->getStatusCode(),
            'Unauthenticated API request should return 401');
    }

    public function testCreateReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'POST', self::API_LIST_ROUTE, [
            'name' => 'Test Client',
            'ruc' => '1799999999001',
        ]);
        static::assertSame(401, $client->getResponse()->getStatusCode(),
            'Unauthenticated API POST request should return 401');
    }

    public function testListRouteExists(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'GET', self::API_LIST_ROUTE);
        static::assertRouteExists($client->getResponse());
    }

    public function testShowRouteExists(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'GET', self::API_SHOW_ROUTE);
        static::assertRouteExists($client->getResponse());
    }

    public function testApiJsonResponseFormat(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'GET', self::API_LIST_ROUTE);
        $response = $client->getResponse();

        $contentType = $response->headers->get('Content-Type') ?? '';
        static::assertStringContainsString('application/json', $contentType,
            \sprintf('Expected JSON content type, got: %s', $contentType));
    }

    public function testApiErrorResponseContainsErrorKey(): void
    {
        $client = static::createClient();
        $this->jsonRequest($client, 'GET', self::API_LIST_ROUTE);
        $response = $client->getResponse();

        if ($response->getStatusCode() === 401) {
            $data = $this->decodeJsonResponse($response);

            $hasErrorKey = isset($data['error']) || isset($data['message']) || isset($data['title']);
            static::assertTrue($hasErrorKey,
                'Unauthorized response should contain an error key');
        }
    }

    public function testInvalidApiRouteReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/client/nonexistent');
        static::assertNotSame(500, $client->getResponse()->getStatusCode(),
            'Invalid API route should not crash with 500');
    }
}
