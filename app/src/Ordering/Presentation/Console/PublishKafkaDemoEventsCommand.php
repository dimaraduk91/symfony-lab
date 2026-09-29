<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Console;

use App\Ordering\Application\Kafka\DomainEventPublisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\UuidV7;

#[AsCommand(name: 'app:kafka:publish-demo-events', description: 'Publish deterministic Order event sequences with orderId as Kafka key.')]
final class PublishKafkaDemoEventsCommand extends Command
{
    public function __construct(private readonly DomainEventPublisher $publisher) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (['order-1', 'order-2', 'order-3', 'order-4'] as $orderId) {
            foreach (['OrderCreated', 'OrderPaid', 'OrderCancelled'] as $eventType) {
                $eventId = (new UuidV7())->toString();
                $this->publisher->publish($eventId, $eventType, $orderId, new \DateTimeImmutable(), ['orderId' => $orderId]);
                $output->writeln(sprintf('published event=%s eventId=%s order=%s key=%s', $eventType, $eventId, $orderId, $orderId));
            }
        }

        return Command::SUCCESS;
    }
}
