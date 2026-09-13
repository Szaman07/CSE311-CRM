<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->collation('utf8mb4_bin');
            $table->unsignedBigInteger('version')->default(1);
            $table->dateTime('archived_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unique('name', 'uq_categories_name');
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id');
            $table->string('sku', 64);
            $table->string('name', 150);
            $table->decimal('unit_price', 10, 2);
            $table->integer('stock_on_hand')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->unsignedBigInteger('version')->default(1);
            $table->dateTime('archived_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unique('sku', 'uq_products_sku');
            $table->foreign('category_id', 'fk_products_category')->references('id')->on('categories')->restrictOnDelete();
            $table->index(['category_id', 'archived_at', 'id'], 'ix_products_category');
            $table->index(['archived_at', 'name', 'id'], 'ix_products_active_name');
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 120);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->dateTime('archived_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index(['archived_at', 'full_name', 'id'], 'ix_customers_active_name');
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('created_by');
            $table->char('request_key', 36);
            $table->char('request_fingerprint', 64);
            $table->string('status', 20)->default('completed');
            $table->foreignId('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unique('request_key', 'uq_sales_request');
            $table->foreign('customer_id', 'fk_sales_customer')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('created_by', 'fk_sales_creator')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cancelled_by', 'fk_sales_canceller')->references('id')->on('users')->restrictOnDelete();
            $table->index(['status', 'created_at', 'id'], 'ix_sales_status_date');
            $table->index(['customer_id', 'created_at', 'id'], 'ix_sales_customer_date');
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->string('product_name', 150);
            $table->string('product_sku', 64);
            $table->unique(['sale_id', 'product_id'], 'uq_sale_items_product');
            $table->unique(['id', 'product_id'], 'uq_sale_items_id_product');
            $table->foreign('sale_id', 'fk_items_sale')->references('id')->on('sales')->restrictOnDelete();
            $table->foreign('product_id', 'fk_items_product')->references('id')->on('products')->restrictOnDelete();
            $table->index(['product_id', 'sale_id'], 'ix_items_product_sale');
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id');
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->integer('quantity_delta');
            $table->integer('resulting_stock');
            $table->string('reason', 20);
            $table->foreignId('created_by');
            $table->char('request_key', 36)->nullable();
            $table->char('request_fingerprint', 64)->nullable();
            $table->string('note', 500)->nullable();
            $table->dateTime('created_at');
            $table->unique(['sale_item_id', 'reason'], 'uq_movements_item_reason');
            $table->unique('request_key', 'uq_movements_request');
            $table->foreign('product_id', 'fk_movements_product')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('created_by', 'fk_movements_actor')->references('id')->on('users')->restrictOnDelete();
            $table->foreign(['sale_item_id', 'product_id'], 'fk_movements_item_product')
                ->references(['id', 'product_id'])->on('sale_items')->restrictOnDelete();
            $table->index(['product_id', 'created_at', 'id'], 'ix_movements_product_time');
            $table->index(['created_at', 'id'], 'ix_movements_time');
        });

        DB::statement('ALTER TABLE categories ADD CONSTRAINT ck_categories_name CHECK (CHAR_LENGTH(TRIM(name)) > 0)');
        DB::statement('ALTER TABLE categories ADD CONSTRAINT ck_categories_version CHECK (version >= 1)');
        DB::statement('ALTER TABLE products MODIFY sku VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement('ALTER TABLE products ADD CONSTRAINT ck_products_price CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT ck_products_stock CHECK (stock_on_hand BETWEEN 0 AND 1000000000)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT ck_products_reorder CHECK (reorder_level BETWEEN 0 AND 1000000000)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT ck_products_version CHECK (version >= 1)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT ck_products_name CHECK (CHAR_LENGTH(TRIM(name)) > 0)');
        DB::statement('ALTER TABLE customers ADD CONSTRAINT ck_customers_name CHECK (CHAR_LENGTH(TRIM(full_name)) > 0)');
        DB::statement('ALTER TABLE customers ADD CONSTRAINT ck_customers_version CHECK (version >= 1)');
        DB::statement('ALTER TABLE sales MODIFY request_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement('ALTER TABLE sales MODIFY request_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement("ALTER TABLE sales ADD CONSTRAINT ck_sales_state CHECK ((status='completed' AND cancelled_by IS NULL AND cancelled_at IS NULL AND cancel_reason IS NULL) OR (status='cancelled' AND cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL AND CHAR_LENGTH(TRIM(cancel_reason)) > 0))");
        DB::statement('ALTER TABLE sale_items MODIFY product_sku VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT ck_items_quantity CHECK (quantity BETWEEN 1 AND 1000000)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT ck_items_price CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE stock_movements MODIFY request_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL');
        DB::statement('ALTER TABLE stock_movements MODIFY request_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT ck_movements_delta CHECK (quantity_delta BETWEEN -1000000 AND 1000000 AND quantity_delta <> 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT ck_movements_result CHECK (resulting_stock BETWEEN 0 AND 1000000000)');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT ck_movements_kind CHECK ((reason IN ('restock','adjustment') AND sale_item_id IS NULL AND request_key IS NOT NULL AND request_fingerprint IS NOT NULL AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) > 0 AND (reason='adjustment' OR quantity_delta>0)) OR (reason='sale' AND sale_item_id IS NOT NULL AND quantity_delta<0 AND request_key IS NULL AND request_fingerprint IS NULL) OR (reason='cancellation' AND sale_item_id IS NOT NULL AND quantity_delta>0 AND request_key IS NULL AND request_fingerprint IS NULL))");

        DB::statement('CREATE OR REPLACE SQL SECURITY INVOKER VIEW sale_totals AS SELECT s.id AS sale_id,s.status,s.customer_id,s.created_at,CAST(COALESCE(SUM(i.quantity*i.unit_price),0) AS DECIMAL(22,2)) AS total FROM sales s LEFT JOIN sale_items i ON i.sale_id=s.id GROUP BY s.id,s.status,s.customer_id,s.created_at');
        DB::statement('CREATE OR REPLACE SQL SECURITY INVOKER VIEW inventory_reconciliation AS SELECT p.id AS product_id,p.stock_on_hand,COALESCE(m.ledger_stock,0) AS ledger_stock,p.stock_on_hand-COALESCE(m.ledger_stock,0) AS difference FROM products p LEFT JOIN (SELECT product_id,SUM(quantity_delta) AS ledger_stock FROM stock_movements GROUP BY product_id) m ON m.product_id=p.id');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS inventory_reconciliation');
        DB::statement('DROP VIEW IF EXISTS sale_totals');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
