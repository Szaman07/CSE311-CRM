# 2. Requirements to ERD to tables

## Model facts before screens

Identify entities, attributes, relationships, cardinality and participation. A screen is not necessarily an entity; a dashboard often queries existing facts. Distinguish a product's current price from its price when sold: different facts belong in products and sale_items.

Read the [project ERD](../docs/DATABASE_DESIGN.md). Categories have zero or many products; every product has exactly one category. Sales have zero/one customer and exactly one creator. A customer may have zero sales. Sales/products form M:N through sale_items.

An FK on products.category_id plus NOT NULL enforces each product's valid category. It does not enforce a minimum product count for each category. UNIQUE(sale_id,product_id) prevents repeated product lines but does not enforce that a sale has an item. That minimum is a transaction rule.

## Mapping rules

- Strong entity -> table with candidate key(s); choose primary key deliberately.
- 1:N -> FK on N side; add NOT NULL for mandatory child participation.
- 1:1 -> unique FK, optionally NOT NULL. This usually guarantees at most one related row, not mandatory existence on both sides.
- M:N -> associative table with both FKs; pair often candidate key.
- Multivalued attribute -> separate child relation, not comma-separated cell values.
- Derived attribute -> calculate unless caching has a measured/explicit reason and maintenance rule.

A weak entity is identified through its owner's key plus a partial key and is existence-dependent. A sale line modeled as (sale_id,line_number) can be a weak entity. Once given a globally identifying surrogate id, don't call it weak merely because it still has an FK. Explain conceptual identification separately from implementation keys.

## Project decisions

Seven tables suffice for this core, not for a full retail ERP. No warehouse_id means one inventory pool. Whole-unit stock excludes weight-based goods. No payment table means sale totals are recorded values, not cash accounting. No suppliers means restock records origin only as a human reason, not a procurement workflow.

Archival preserves identity and references. It is a state field, not a replacement for access control. Product SKU is unique across archived records; reusing it would confuse history and restoration.

sale_items snapshots are intentional historical attributes. A later product rename/reprice must not change receipt lines. Customer identity is linked to the current customer row; legal address snapshots are outside scope.

## Exercises

1. Add multiple warehouses conceptually. **Answer:** warehouses and product_stock(product_id,warehouse_id,quantity), transfer semantics and movement location; a column on products alone would not support stock in several warehouses. This is future scope, not an initial schema change.
2. May customer email be a key? **Answer:** only if the business defines it required and unique. Here email is optional and shared/duplicate entries are allowed, so no.
3. Does a surrogate item ID make UNIQUE(sale_id,product_id) unnecessary? **Answer:** no; ID uniqueness doesn't enforce cart consolidation.
4. Draw optionality for cancellation actor. **Answer:** completed sale has none, cancelled sale exactly one; CHECK plus service enforces state-dependent cardinality.
5. Is a receipt table required because there is a receipt screen? **Answer:** no; header and snapshotted items already supply the view.

Gate: draw the ERD from prose and state, for each edge, what the database enforces and what it cannot enforce alone.
