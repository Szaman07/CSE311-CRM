-- CRM reference schema, synchronized 2026-09-12 with reviewed Laravel migrations.
-- Select a NEW EMPTY disposable database explicitly before importing.
-- No DROP/CREATE DATABASE/USE. InnoDB; strict SQL mode; UTC connection.
-- CHECK enforcement, FKs, binary uniqueness, views and migrations passed on XAMPP MariaDB 10.4.32.
-- Laravel migrations are executable authority; this file remains readable DBMS/viva reference DDL.
CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) COLLATE utf8mb4_bin NOT NULL,
 password VARCHAR(255) NOT NULL,
 role VARCHAR(20) NOT NULL,
 is_active TINYINT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT uq_users_email UNIQUE (email),
 CONSTRAINT ck_users_role CHECK (role IN ('manager','sales_clerk')),
 CONSTRAINT ck_users_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) COLLATE utf8mb4_bin NOT NULL,
 version BIGINT UNSIGNED NOT NULL DEFAULT 1,
 archived_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT uq_categories_name UNIQUE (name),
 CONSTRAINT ck_categories_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
 CONSTRAINT ck_categories_version CHECK (version >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NOT NULL,
 sku VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 name VARCHAR(150) NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 stock_on_hand INT NOT NULL DEFAULT 0,
 reorder_level INT NOT NULL DEFAULT 0,
 version BIGINT UNSIGNED NOT NULL DEFAULT 1,
 archived_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT uq_products_sku UNIQUE (sku),
 CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
 CONSTRAINT ck_products_price CHECK (unit_price >= 0),
 CONSTRAINT ck_products_stock CHECK (stock_on_hand BETWEEN 0 AND 1000000000),
 CONSTRAINT ck_products_reorder CHECK (reorder_level BETWEEN 0 AND 1000000000),
 CONSTRAINT ck_products_version CHECK (version >= 1),
 CONSTRAINT ck_products_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
 INDEX ix_products_category (category_id, archived_at, id),
 INDEX ix_products_active_name (archived_at, name, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(255) NULL,
 phone VARCHAR(30) NULL,
 version BIGINT UNSIGNED NOT NULL DEFAULT 1,
 archived_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT ck_customers_name CHECK (CHAR_LENGTH(TRIM(full_name)) > 0),
 CONSTRAINT ck_customers_version CHECK (version >= 1),
 INDEX ix_customers_active_name (archived_at, full_name, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 request_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 request_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'completed',
 cancelled_by BIGINT UNSIGNED NULL,
 cancelled_at DATETIME NULL,
 cancel_reason VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT uq_sales_request UNIQUE (request_key),
 CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_sales_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_sales_canceller FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT ck_sales_state CHECK (
  (status = 'completed' AND cancelled_by IS NULL AND cancelled_at IS NULL AND cancel_reason IS NULL)
  OR (status = 'cancelled' AND cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL
      AND cancel_reason IS NOT NULL AND CHAR_LENGTH(TRIM(cancel_reason)) > 0)
 ),
 INDEX ix_sales_status_date (status, created_at, id),
 INDEX ix_sales_customer_date (customer_id, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sale_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 quantity INT NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 product_name VARCHAR(150) NOT NULL,
 product_sku VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 CONSTRAINT uq_sale_items_product UNIQUE (sale_id, product_id),
 -- Redundant superkey enables composite FK enforcing movement's product match.
 CONSTRAINT uq_sale_items_id_product UNIQUE (id, product_id),
 CONSTRAINT fk_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
 CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
 CONSTRAINT ck_items_quantity CHECK (quantity BETWEEN 1 AND 1000000),
 CONSTRAINT ck_items_price CHECK (unit_price >= 0),
 INDEX ix_items_product_sale (product_id, sale_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 sale_item_id BIGINT UNSIGNED NULL,
 quantity_delta INT NOT NULL,
 resulting_stock INT NOT NULL,
 reason VARCHAR(20) NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 request_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
 request_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
 note VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT uq_movements_item_reason UNIQUE (sale_item_id, reason),
 CONSTRAINT uq_movements_request UNIQUE (request_key),
 CONSTRAINT fk_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
 CONSTRAINT fk_movements_item_product FOREIGN KEY (sale_item_id, product_id)
   REFERENCES sale_items(id, product_id) ON DELETE RESTRICT,
 CONSTRAINT fk_movements_actor FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT ck_movements_delta CHECK (quantity_delta BETWEEN -1000000 AND 1000000 AND quantity_delta <> 0),
 CONSTRAINT ck_movements_result CHECK (resulting_stock BETWEEN 0 AND 1000000000),
 CONSTRAINT ck_movements_kind CHECK (
  (reason IN ('restock','adjustment') AND sale_item_id IS NULL
    AND request_key IS NOT NULL AND request_fingerprint IS NOT NULL
    AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) > 0
    AND (reason = 'adjustment' OR quantity_delta > 0))
  OR (reason = 'sale' AND sale_item_id IS NOT NULL AND quantity_delta < 0
    AND request_key IS NULL AND request_fingerprint IS NULL)
  OR (reason = 'cancellation' AND sale_item_id IS NOT NULL AND quantity_delta > 0
    AND request_key IS NULL AND request_fingerprint IS NULL)
 ),
 INDEX ix_movements_product_time (product_id, created_at, id),
 INDEX ix_movements_time (created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SQL SECURITY INVOKER: view access does not elevate beyond caller table grants.
CREATE OR REPLACE SQL SECURITY INVOKER VIEW sale_totals AS
SELECT s.id AS sale_id, s.status, s.customer_id, s.created_at,
       CAST(COALESCE(SUM(i.quantity * i.unit_price), 0) AS DECIMAL(22,2)) AS total
FROM sales s LEFT JOIN sale_items i ON i.sale_id = s.id
GROUP BY s.id, s.status, s.customer_id, s.created_at;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW inventory_reconciliation AS
SELECT p.id AS product_id, p.stock_on_hand,
       COALESCE(m.ledger_stock, 0) AS ledger_stock,
       p.stock_on_hand - COALESCE(m.ledger_stock, 0) AS difference
FROM products p
LEFT JOIN (
 SELECT product_id, SUM(quantity_delta) AS ledger_stock
 FROM stock_movements GROUP BY product_id
) m ON m.product_id = p.id;
