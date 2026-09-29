<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Kafka;

final readonly class KafkaConsumerFactory
{
    public function __construct(private string $brokers) {}

    /** @param callable(string): void $log */
    public function create(string $groupId, callable $log): \RdKafka\KafkaConsumer
    {
        $config = new \RdKafka\Conf();
        $config->set('bootstrap.servers', $this->brokers);
        $config->set('group.id', $groupId);
        $config->set('enable.auto.commit', 'false');
        $config->set('enable.auto.offset.store', 'false');
        $config->set('auto.offset.reset', 'earliest');
        $config->setRebalanceCb(static function (\RdKafka\KafkaConsumer $consumer, int $error, ?array $partitions) use ($log): void {
            if ($error === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                $consumer->assign($partitions);
                $log('Assigned partitions: '.implode(', ', array_map(static fn (\RdKafka\TopicPartition $p): string => (string) $p->getPartition(), $partitions ?? [])));
                return;
            }
            if ($error === RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
                $log('Partitions revoked (group rebalance).');
                $consumer->assign(null);
                return;
            }
            throw new \RuntimeException(\rd_kafka_err2str($error), $error);
        });

        $consumer = new \RdKafka\KafkaConsumer($config);
        $consumer->subscribe(['order.events']);

        return $consumer;
    }
}
