<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Kafka analytics projection, durable idempotency markers and audit log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_event_statistics (event_type VARCHAR(100) NOT NULL, processed_count BIGINT NOT NULL DEFAULT 0, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (event_type))');
        $this->addSql('CREATE TABLE processed_kafka_events (event_id VARCHAR(36) NOT NULL, consumer_name VARCHAR(100) NOT NULL, processed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (event_id, consumer_name))');
        $this->addSql('CREATE TABLE order_event_audit (event_id VARCHAR(36) NOT NULL, event_type VARCHAR(100) NOT NULL, aggregate_id VARCHAR(36) NOT NULL, occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, payload JSONB NOT NULL, processed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (event_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_event_audit');
        $this->addSql('DROP TABLE processed_kafka_events');
        $this->addSql('DROP TABLE order_event_statistics');
    }
}
