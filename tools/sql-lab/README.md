# PostgreSQL SQL lab

## Purpose

This dataset provides a deliberately unoptimized, non-uniform baseline for practicing PostgreSQL `JOIN`, `EXISTS`, `GROUP BY`, CTEs, window functions, indexes, pagination, `EXPLAIN ANALYZE`, planner behavior, and query optimization.

It creates approximately 10,000 customers, 300 sellers, 5,000 products, 200,000 orders, and 376,000 order items. Product and seller popularity is intentionally skewed.

## Prepare and seed the database

Start the project and apply Doctrine migrations from the repository root:

```bash
docker compose up -d --build
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

Load the dataset through the PostgreSQL 16 `db` service:

```bash
docker compose exec -T db psql -U symfony -d symfony < tools/sql-lab/seed.sql
```

`seed.sql` starts by including `truncate.sql`, so running the same command recreates the complete dataset. The generated data changes between runs because distributions use `random()`.

The script ends with `ANALYZE` rather than `VACUUM (ANALYZE)`. Fresh planner statistics are required here; reclaiming storage is not, and `VACUUM` cannot run inside a transaction if this script is later wrapped by a caller.

## Clear the dataset

This removes only the five lab/domain tables and resets the `order_items` identity sequence:

```bash
docker compose exec -T db psql -U symfony -d symfony < tools/sql-lab/truncate.sql
```

`RESTART IDENTITY` has no effect on the application-generated string UUIDs. It is relevant only to the integer identity used by `order_items.id`.

## Check row counts

The seed prints counts and distribution checks automatically. To check counts separately:

```bash
docker compose exec -T db psql -U symfony -d symfony -c "
SELECT 'customers' AS table_name, count(*) AS row_count FROM customers
UNION ALL SELECT 'sellers', count(*) FROM sellers
UNION ALL SELECT 'products', count(*) FROM products
UNION ALL SELECT 'orders', count(*) FROM orders
UNION ALL SELECT 'order_items', count(*) FROM order_items
ORDER BY table_name;"
```

The current domain has no `failed` status or `paid_at` column. The seed therefore uses its four valid statuses with an approximate `paid` 70%, `cancelled` 12%, `pending` 8%, and `completed` 10% distribution. No speculative query-performance indexes are created; only primary-key and unique-email indexes exist in addition to the pre-existing `order_items.order_id` index.
