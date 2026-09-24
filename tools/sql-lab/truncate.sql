\set ON_ERROR_STOP on

BEGIN;

TRUNCATE TABLE order_items, orders, products, sellers, customers RESTART IDENTITY;

COMMIT;
