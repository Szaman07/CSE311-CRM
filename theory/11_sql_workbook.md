# SQL workbook — attempt before reading solutions

Use the [inventory reference schema](../docs/schema/inventory_reference.mysql.sql) only in a disposable database after runtime compatibility checks. The [seed specification](../docs/DATABASE_DESIGN.md) defines hand-checkable results. Named parameters below are application placeholders; replace with explicit test literals in phpMyAdmin. Queries are teaching references awaiting actual-engine execution.

## Questions

1. Show active low-stock products.
2. Count products per category, retaining empty categories.
3. Show products never included in a completed sale.
4. Calculate every sale's total, including cancelled sales with status.
5. Find products with at least two units sold in completed sales.
6. Show customers with no completed sales.
7. Calculate completed value over a local date interval.
8. Reconcile all product balances.
9. Find customers buying from every active category.
10. List top products with deterministic tie ordering.
11. Find missing/wrong sale deductions.
12. Demonstrate an atomic restock and explain why its production version needs more rules.

## Solutions

### 1–3: filtering, outer join and anti-join

```sql
SELECT id, sku, name, stock_on_hand, reorder_level
FROM products
WHERE archived_at IS NULL AND stock_on_hand <= reorder_level
ORDER BY stock_on_hand, id;

SELECT c.id, c.name, COUNT(p.id) AS product_count
FROM categories c LEFT JOIN products p ON p.category_id=c.id
GROUP BY c.id,c.name ORDER BY c.id;

SELECT p.id,p.name FROM products p
WHERE NOT EXISTS (
 SELECT 1 FROM sale_items i JOIN sales s ON s.id=i.sale_id
 WHERE i.product_id=p.id AND s.status='completed'
)
ORDER BY p.id;
```

Q2 includes archived products unless you put their active filter in ON. Putting p.archived_at IS NULL in WHERE is subtle: categories with only archived products disappear; specify desired semantics first. Q3 returns eraser and marker in the demo; cancelled-only purchases also don't count.

### 4–7: aggregation and empty results

```sql
SELECT sale_id,status,total FROM sale_totals ORDER BY sale_id;

SELECT i.product_id,SUM(i.quantity) AS units
FROM sale_items i JOIN sales s ON s.id=i.sale_id
WHERE s.status='completed'
GROUP BY i.product_id HAVING SUM(i.quantity)>=2
ORDER BY i.product_id;

SELECT c.id,c.full_name FROM customers c
WHERE NOT EXISTS (
 SELECT 1 FROM sales s WHERE s.customer_id=c.id AND s.status='completed'
)
ORDER BY c.id;

SELECT CAST(COALESCE(SUM(i.quantity*i.unit_price),0) AS DECIMAL(22,2)) AS recorded_value
FROM sales s JOIN sale_items i ON i.sale_id=s.id
WHERE s.status='completed'
 AND s.created_at>=:start_utc AND s.created_at<:end_utc;
```

Demo Q4 totals are 70.00 completed and 10.00 cancelled. Q5 pen has 2. Q6 customer B. Q7 covering both sale timestamps returns 70.00; empty interval returns 0.00. Do not exclude cancellations from the receipt/history query.

### 8–10: reconciliation, division, ranking

```sql
SELECT * FROM inventory_reconciliation
WHERE difference<>0 ORDER BY product_id;

SELECT c.id,c.full_name FROM customers c
WHERE NOT EXISTS (
 SELECT 1 FROM categories cat
 WHERE cat.archived_at IS NULL
 AND NOT EXISTS (
  SELECT 1 FROM sales s
  JOIN sale_items i ON i.sale_id=s.id
  JOIN products p ON p.id=i.product_id
  WHERE s.customer_id=c.id AND s.status='completed'
    AND p.category_id=cat.id
 )
)
ORDER BY c.id;

SELECT i.product_id,SUM(i.quantity) AS units,
 CAST(SUM(i.quantity*i.unit_price) AS DECIMAL(22,2)) AS recorded_value
FROM sale_items i JOIN sales s ON s.id=i.sale_id
WHERE s.status='completed'
GROUP BY i.product_id
ORDER BY units DESC,i.product_id ASC
LIMIT 10;
```

Q8 returns no rows in a valid fixture. Q9 uses **current** product categories, so later reassignment changes classification; this is a theory exercise, not a historical category report promised in SRS. Empty required categories returns all customers. An active never-purchased category prevents anyone qualifying.

### 11: detect missing or wrong sale movements

```sql
SELECT i.id,i.product_id,i.quantity,m.id AS movement_id,m.quantity_delta
FROM sale_items i
LEFT JOIN stock_movements m ON m.sale_item_id=i.id AND m.reason='sale'
WHERE m.id IS NULL OR m.quantity_delta<>-i.quantity
ORDER BY i.id;
```

A separate cancellation check must compare header state, expected count and positive quantity. Balance equality alone can miss an offsetting pair of incorrect movements.

### 12: transaction anatomy (incomplete lab outline)

```sql
START TRANSACTION;
SELECT id,stock_on_hand,version FROM products WHERE id=:id FOR UPDATE;
-- Application validates manager, active state, key, bounds and expected rules.
-- UPDATE product balance/version and INSERT its matching movement here.
-- COMMIT only if both succeed; otherwise ROLLBACK.
ROLLBACK; -- This outline intentionally performs no stock write.
```

The actual restock service also needs canonical retry resolution, one connection, safe exception rollback, UTC timestamps and actor attribution. A single transaction in one console does not prove concurrency safety.

## Additional independent practice

Rewrite Q3 using a correctly filtered LEFT anti-join. Rank products within category using a window function and explain current-category semantics. Write a customer summary that retains customers with no sales and separates completed/cancelled counts. Add two equal-price products and show why SUM(DISTINCT price) is wrong.

For every query record: natural-language requirement, expected rows, SQL, actual rows, edge-case fixture and explanation. This becomes academic evidence, not just a screenshot collection.
