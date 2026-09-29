<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Application\Kafka;

use App\Ordering\Application\Kafka\AnalyticsEventProcessor;
use App\Ordering\Application\Kafka\OrderEventEnvelope;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AnalyticsEventProcessorTest extends TestCase
{
    #[DataProvider('processingCases')]
    public function testProjectionChangesOnlyForNewEvent(int $markerInsertCount, bool $expected, int $statementCount): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());
        $counts = $markerInsertCount === 1 ? [1, 1] : [0];
        $connection->expects(self::exactly($statementCount))->method('executeStatement')->willReturnOnConsecutiveCalls(...$counts);

        $event = new OrderEventEnvelope('event-1', 'OrderPaid', 'order-1', new \DateTimeImmutable());
        self::assertSame($expected, (new AnalyticsEventProcessor($connection))->process($event));
    }

    /** @return iterable<string, array{int, bool, int}> */
    public static function processingCases(): iterable
    {
        yield 'new event increments projection' => [1, true, 2];
        yield 'duplicate event does not touch projection' => [0, false, 1];
    }
}
