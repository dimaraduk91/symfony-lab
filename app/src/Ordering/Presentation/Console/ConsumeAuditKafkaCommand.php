<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Console;

use App\Ordering\Application\Kafka\AuditEventProcessor;
use App\Ordering\Application\Kafka\OrderEventEnvelope;
use App\Ordering\Infrastructure\Kafka\KafkaConsumerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:kafka:consume-audit', description: 'Consume order.events as the independent order-audit group.')]
final class ConsumeAuditKafkaCommand extends Command
{
    public function __construct(private readonly KafkaConsumerFactory $factory, private readonly AuditEventProcessor $processor) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $group = 'order-audit';
        $consumer = $this->factory->create($group, static fn (string $line) => $output->writeln($line));
        while (true) {
            $message = $consumer->consume(1_000);
            if ($message->err === RD_KAFKA_RESP_ERR__TIMED_OUT || $message->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) { continue; }
            if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) { throw new \RuntimeException($message->errstr(), $message->err); }
            $event = OrderEventEnvelope::fromJson((string) $message->payload);
            $processed = $this->processor->process($event);
            $output->writeln(sprintf('event=%s eventId=%s order=%s partition=%d offset=%d group=%s%s', $event->eventType, $event->eventId, $event->aggregateId, $message->partition, $message->offset, $group, $processed ? '' : ' duplicate=ignored'));
            $consumer->commit($message);
        }
    }
}
