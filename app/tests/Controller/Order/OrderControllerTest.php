<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

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

        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'customerId' => 'customer-123',
                'items' => [[
                    'productId' => 'product-456',
                    'sellerId' => 'seller-789',
                    'quantity' => 2,
                    'price' => 1999,
                ]],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"success"}', (string) $client->getResponse()->getContent());
    }

    public function testCreateRejectsAnInvalidOrderPayload(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"customerId":"customer-123","items":[{"productId":"","sellerId":"","quantity":0,"price":0}]}',
        );

        self::assertResponseStatusCodeSame(422);
    }
}
