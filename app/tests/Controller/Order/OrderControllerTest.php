<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderControllerTest extends WebTestCase
{
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
            content: '{"customerId":"customer-123","items":[{"productId":"","quantity":0,"price":0}]}',
        );

        self::assertResponseStatusCodeSame(422);
    }
}
