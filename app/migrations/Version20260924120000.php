<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924120000 extends AbstractMigration
{
    private const LEGACY_SELLER_ID = '00000000-0000-7000-8000-000000000000';

    public function getDescription(): string
    {
        return 'Add customers, sellers and products and link them to orders and order items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customers (id VARCHAR(36) NOT NULL, email VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_62534E21E7927C74 ON customers (email)');
        $this->addSql('CREATE TABLE sellers (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE products (id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');

        // Preserve existing aggregate rows before enforcing the new foreign keys.
        $this->addSql("INSERT INTO customers (id, email, created_at) SELECT DISTINCT customer_id, 'legacy-' || md5(customer_id) || '@example.test', MIN(created_at) OVER (PARTITION BY customer_id) FROM orders ON CONFLICT (id) DO NOTHING");
        $this->addSql("INSERT INTO products (id, title, created_at) SELECT DISTINCT product_id, 'Legacy product ' || product_id, CURRENT_TIMESTAMP FROM order_items ON CONFLICT (id) DO NOTHING");
        $this->addSql("INSERT INTO sellers (id, name, created_at) VALUES ('" . self::LEGACY_SELLER_ID . "', 'Legacy seller', CURRENT_TIMESTAMP)");

        $this->addSql('ALTER TABLE order_items ADD seller_id VARCHAR(36) DEFAULT NULL');
        $this->addSql("UPDATE order_items SET seller_id = '" . self::LEGACY_SELLER_ID . "'");
        $this->addSql('ALTER TABLE order_items ALTER seller_id SET NOT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_ORDERS_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_ORDER_ITEMS_PRODUCT FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_ORDER_ITEMS_SELLER FOREIGN KEY (seller_id) REFERENCES sellers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_ORDERS_CUSTOMER');
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_ORDER_ITEMS_PRODUCT');
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_ORDER_ITEMS_SELLER');
        $this->addSql('ALTER TABLE order_items DROP COLUMN seller_id');
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE sellers');
        $this->addSql('DROP TABLE products');
    }
}
