\set ON_ERROR_STOP on
\timing on

BEGIN;

-- Keep the seed idempotent when it is piped to psql from the host.
TRUNCATE TABLE order_items, orders, products, sellers, customers RESTART IDENTITY;

INSERT INTO customers (id, email, created_at)
SELECT
    md5('customer-' || n::text)::uuid::text,
    'customer-' || n || '@example.test',
    CURRENT_TIMESTAMP - random() * INTERVAL '730 days'
FROM generate_series(1, 10000) AS source(n);

INSERT INTO sellers (id, name, created_at)
SELECT
    md5('seller-' || n::text)::uuid::text,
    'Seller ' || n,
    CURRENT_TIMESTAMP - random() * INTERVAL '1095 days'
FROM generate_series(1, 300) AS source(n);

INSERT INTO products (id, title, created_at)
SELECT
    md5('product-' || n::text)::uuid::text,
    'Product ' || n,
    CURRENT_TIMESTAMP - random() * INTERVAL '730 days'
FROM generate_series(1, 5000) AS source(n);

CREATE TEMPORARY TABLE sql_lab_customers AS
SELECT id, row_number() OVER (ORDER BY id) AS n FROM customers;

CREATE TEMPORARY TABLE sql_lab_sellers AS
SELECT id, row_number() OVER (ORDER BY id) AS n FROM sellers;

CREATE TEMPORARY TABLE sql_lab_products AS
SELECT id, row_number() OVER (ORDER BY id) AS n FROM products;

CREATE TEMPORARY TABLE sql_lab_orders (
    n integer PRIMARY KEY,
    id varchar(36) NOT NULL,
    created_at timestamptz NOT NULL
);

INSERT INTO sql_lab_orders (n, id, created_at)
SELECT
    source.n,
    md5('order-' || source.n::text)::uuid::text,
    CURRENT_TIMESTAMP - distribution.age * INTERVAL '365 days'
FROM generate_series(1, 200000) AS source(n)
CROSS JOIN LATERAL (SELECT random() + source.n * 0 AS age) AS distribution;

INSERT INTO orders (status, amount, id, customer_id, currency, created_at, updated_at)
SELECT
    CASE
        WHEN distribution.status_roll < 0.70 THEN 'paid'
        WHEN distribution.status_roll < 0.82 THEN 'cancelled'
        WHEN distribution.status_roll < 0.90 THEN 'pending'
        ELSE 'completed'
    END,
    0,
    generated.id,
    customer.id,
    'EUR',
    generated.created_at,
    generated.created_at + distribution.update_delay * (CURRENT_TIMESTAMP - generated.created_at)
FROM sql_lab_orders AS generated
CROSS JOIN LATERAL (
    SELECT random() + generated.n * 0 AS status_roll,
           random() + generated.n * 0 AS update_delay,
           1 + floor((random() + generated.n * 0) * 10000)::integer AS customer_n
) AS distribution
JOIN sql_lab_customers AS customer
  ON customer.n = distribution.customer_n;

INSERT INTO order_items (product_id, seller_id, quantity, price, order_id)
SELECT
    product.id,
    seller.id,
    CASE
        WHEN attributes.quantity_roll < 0.82 THEN 1
        WHEN attributes.quantity_roll < 0.95 THEN 2
        WHEN attributes.quantity_roll < 0.99 THEN 3
        ELSE 4 + floor(random() * 2)::integer
    END,
    199 + floor(random() * 49802)::integer,
    generated.id
FROM sql_lab_orders AS generated
CROSS JOIN LATERAL (
    SELECT random() + generated.n * 0 AS item_count_roll
) AS item_distribution
CROSS JOIN LATERAL generate_series(
    1,
    CASE
        WHEN item_distribution.item_count_roll < 0.50 THEN 1
        WHEN item_distribution.item_count_roll < 0.75 THEN 2
        WHEN item_distribution.item_count_roll < 0.90 THEN 3
        WHEN item_distribution.item_count_roll < 0.97 THEN 4
        ELSE 5
    END
) AS item_number(n)
CROSS JOIN LATERAL (
    SELECT random() + item_number.n * 0 AS quantity_roll,
           1 + floor(power(random() + item_number.n * 0, 3) * 5000)::integer AS product_n,
           1 + floor(power(random() + item_number.n * 0, 4) * 300)::integer AS seller_n
) AS attributes
JOIN sql_lab_products AS product ON product.n = attributes.product_n
JOIN sql_lab_sellers AS seller ON seller.n = attributes.seller_n;

UPDATE orders AS target
SET amount = totals.amount
FROM (
    SELECT order_id, SUM(price * quantity)::integer AS amount
    FROM order_items
    GROUP BY order_id
) AS totals
WHERE totals.order_id = target.id;

COMMIT;

-- ANALYZE is safe in this rerunnable script and does not require VACUUM's
-- transaction restrictions or an exclusive maintenance operation.
ANALYZE;

-- Verification: row counts.
SELECT 'customers' AS table_name, count(*) AS row_count FROM customers
UNION ALL SELECT 'sellers', count(*) FROM sellers
UNION ALL SELECT 'products', count(*) FROM products
UNION ALL SELECT 'orders', count(*) FROM orders
UNION ALL SELECT 'order_items', count(*) FROM order_items
ORDER BY table_name;

-- Verification: order status distribution.
SELECT
    status,
    count(*) AS order_count,
    round(100.0 * count(*) / SUM(count(*)) OVER (), 2) AS percentage
FROM orders
GROUP BY status
ORDER BY order_count DESC;

-- Verification: most active sellers.
SELECT seller_id, count(*) AS items_count
FROM order_items
GROUP BY seller_id
ORDER BY items_count DESC
LIMIT 20;

-- Verification: most popular products.
SELECT product_id, count(*) AS items_count
FROM order_items
GROUP BY product_id
ORDER BY items_count DESC
LIMIT 20;
