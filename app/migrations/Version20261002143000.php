<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store HTTP correlation and W3C trace context with outbox messages.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE outbox_messages ADD request_id VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE outbox_messages ADD traceparent VARCHAR(55) DEFAULT NULL');
        $this->addSql('ALTER TABLE outbox_messages ADD tracestate TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE outbox_messages DROP request_id');
        $this->addSql('ALTER TABLE outbox_messages DROP traceparent');
        $this->addSql('ALTER TABLE outbox_messages DROP tracestate');
    }
}
