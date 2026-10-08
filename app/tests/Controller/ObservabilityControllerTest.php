<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ObservabilityControllerTest extends WebTestCase
{
    public function testRequestIdIsGeneratedAndReturned(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            (string) $client->getResponse()->headers->get('X-Request-ID'),
        );
    }

    public function testValidRequestIdIsPreserved(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health', server: ['HTTP_X_REQUEST_ID' => 'load-test.request-42']);

        self::assertResponseHeaderSame('X-Request-ID', 'load-test.request-42');
    }

    public function testInvalidRequestIdIsReplaced(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health', server: ['HTTP_X_REQUEST_ID' => "invalid\nvalue"]);

        self::assertResponseIsSuccessful();
        self::assertNotSame('invalid value', $client->getResponse()->headers->get('X-Request-ID'));
        self::assertNotEmpty($client->getResponse()->headers->get('X-Request-ID'));
    }

    public function testHttpRequestIsExportedAsPrometheusMetrics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/metrics');
        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('http_requests_total{method="GET",route="app_health",status="200"}', $body);
        self::assertStringContainsString('http_request_duration_seconds_bucket{method="GET",route="app_health"', $body);
        self::assertStringContainsString('outbox_messages_pending', $body);
    }
}
