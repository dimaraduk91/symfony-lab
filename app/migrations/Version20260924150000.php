<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes used by order search filters and ordering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_ORDERS_STATUS_CREATED_AT ON orders (status, created_at DESC)');
        $this->addSql('CREATE INDEX IDX_ORDERS_CREATED_AT ON orders (created_at DESC)');
        $this->addSql('CREATE INDEX IDX_ORDER_ITEMS_SELLER_ORDER ON order_items (seller_id, order_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ORDERS_STATUS_CREATED_AT');
        $this->addSql('DROP INDEX IDX_ORDERS_CREATED_AT');
        $this->addSql('DROP INDEX IDX_ORDER_ITEMS_SELLER_ORDER');
    }
}
