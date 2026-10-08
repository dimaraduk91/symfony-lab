<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderControllerTest extends WebTestCase
{
    public function testSearchRejectsInvalidFilters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search?status=unknown&limit=101');

        self::assertResponseStatusCodeSame(400);
        self::assertJsonStringEqualsJsonString(
            '{"error":"status must be one of: pending, paid, completed, cancelled."}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testSearchRejectsAnInvalidDateRange(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search?from=2026-09-02&to=2026-09-01');

        self::assertResponseStatusCodeSame(400);
        self::assertJsonStringEqualsJsonString(
            '{"error":"from must be earlier than or equal to to."}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testCreateAcceptsAValidOrderPayload(): void
    {
        $client = static::createClient();
        $connection = static::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        $connection->executeStatement(<<<'SQL'
            INSERT INTO customers (id, email, created_at)
            VALUES ('00000000-0000-7000-8000-000000000011', 'order-test@example.test', CURRENT_TIMESTAMP)
            ON CONFLICT DO NOTHING
            SQL);
        $connection->executeStatement(<<<'SQL'
            INSERT INTO products (id, title, created_at)
            VALUES ('00000000-0000-7000-8000-000000000012', 'Order test product', CURRENT_TIMESTAMP)
            ON CONFLICT DO NOTHING
            SQL);
        $connection->executeStatement(<<<'SQL'
            INSERT INTO sellers (id, name, created_at)
            VALUES ('00000000-0000-7000-8000-000000000013', 'Order test seller', CURRENT_TIMESTAMP)
            ON CONFLICT DO NOTHING
            SQL);

        $client->request(
            'POST',
            '/api/orders',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_IDEMPOTENCY_KEY' => 'order-controller-test-create',
            ],
            content: json_encode([
                'customerId' => '00000000-0000-7000-8000-000000000011',
                'currency' => 'USD',
                'items' => [[
                    'productId' => '00000000-0000-7000-8000-000000000012',
                    'sellerId' => '00000000-0000-7000-8000-000000000013',
                    'quantity' => 2,
                    'price' => 1999,
                ]],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('00000000-0000-7000-8000-000000000011', $response['customerId']);
        self::assertSame('pending', $response['status']);
        self::assertSame(3998, $response['amount']);
    }

    public function testCreateRejectsAnInvalidOrderPayload(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"customerId":"customer-123","currency":"USD","items":[{"productId":"","sellerId":"","quantity":0,"price":0}]}',
        );

        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{
                "error": "Validation failed.",
                "errors": {
                    "items[0].productId": ["This value should not be blank."],
                    "items[0].sellerId": ["This value should not be blank."],
                    "items[0].quantity": ["This value should be positive."],
                    "items[0].price": ["This value should be positive."]
                }
            }',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testCreateReturnsTheFieldForPayloadTypeErrors(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"customerId":123,"currency":"USD","items":[{"productId":"product-456","sellerId":"seller-789","quantity":"two","price":1999}]}',
        );

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{
                "error": "Validation failed.",
                "errors": {
                    "customerId": ["This value should be of type string."],
                    "items[0].quantity": ["This value should be of type int."]
                }
            }',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testCreateReturnsErrorsForMissingRequiredFields(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"currency":"USD","items":[]}',
        );

        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{
                "error": "Validation failed.",
                "errors": {
                    "customerId": ["This value should be of type string."]
                }
            }',
            (string) $client->getResponse()->getContent(),
        );
    }
}
