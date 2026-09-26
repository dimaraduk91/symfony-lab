<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260926120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add transactional outbox and durable consumer idempotency'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE outbox_messages (id VARCHAR(36) NOT NULL, type VARCHAR(100) NOT NULL, payload JSONB NOT NULL, occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, published_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, attempts INT NOT NULL DEFAULT 0, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_OUTBOX_UNPUBLISHED ON outbox_messages (occurred_at) WHERE published_at IS NULL');
        $this->addSql('CREATE TABLE processed_messages (consumer_name VARCHAR(255) NOT NULL, message_key VARCHAR(128) NOT NULL, processed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (consumer_name, message_key))');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE processed_messages'); $this->addSql('DROP TABLE outbox_messages'); }
}
