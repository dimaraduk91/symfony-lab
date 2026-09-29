<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Console;

use App\Ordering\Application\Kafka\AnalyticsEventProcessor;
use App\Ordering\Application\Kafka\OrderEventEnvelope;
use App\Ordering\Infrastructure\Kafka\KafkaConsumerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:kafka:consume-analytics', description: 'Consume order.events as the order-analytics group.')]
final class ConsumeAnalyticsKafkaCommand extends Command
{
    public function __construct(private readonly KafkaConsumerFactory $factory, private readonly AnalyticsEventProcessor $processor) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('fail-after-processing', null, InputOption::VALUE_NONE, 'Exit after DB commit but before Kafka offset commit (development experiment).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumer = $this->factory->create(AnalyticsEventProcessor::CONSUMER_NAME, static fn (string $line) => $output->writeln($line));
        while (true) {
            $message = $consumer->consume(1_000);
            if ($message->err === RD_KAFKA_RESP_ERR__TIMED_OUT || $message->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) { continue; }
            if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) { throw new \RuntimeException($message->errstr(), $message->err); }

            $event = OrderEventEnvelope::fromJson((string) $message->payload);
            $processed = $this->processor->process($event);
            $output->writeln(sprintf('event=%s eventId=%s order=%s partition=%d offset=%d group=%s%s', $event->eventType, $event->eventId, $event->aggregateId, $message->partition, $message->offset, AnalyticsEventProcessor::CONSUMER_NAME, $processed ? '' : ' duplicate=ignored'));
            if ($input->getOption('fail-after-processing')) {
                $output->writeln('<error>Intentional failure after DB processing and before offset commit.</error>');
                return Command::FAILURE;
            }
            // A crash before this explicit commit causes Kafka to redeliver the record (at-least-once).
            $consumer->commit($message);
        }
    }
}
