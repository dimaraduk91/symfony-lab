<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InternalsControllerTest extends WebTestCase
{
    public function testLifecycleEndpointReturnsSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/internals/lifecycle');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ok"}', (string) $client->getResponse()->getContent());
    }

    public function testRequestListenerCanShortCircuitControllerExecution(): void
    {
        $client = static::createClient();
        $client->request('GET', '/internals/lifecycle?shortCircuit=1');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"source":"kernel.request"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testExceptionEndpointReturnsServerError(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        $client->request('GET', '/internals/exception');

        self::assertResponseStatusCodeSame(500);
    }
}
