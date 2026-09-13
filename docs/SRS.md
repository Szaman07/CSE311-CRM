# Software requirements specification — NexaStock

Version 2.1, 13 September 2026. This document defines required behavior. Implementation and executed evidence are tracked separately in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Purpose and constraints

A store's staff manages products and customers, records stock receipts/corrections, sells available products, and inspects a stock ledger. A manager can cancel a whole sale, restoring stock once.

Confirmed: three months; XAMPP mandatory; SQL needs relearning; JavaScript/React are learning goals; PostgreSQL later. Team size, weekly availability, exact rubric, allowed frameworks, and installed runtime versions remain unknown. Plan assumes one primary learner and approximately 12–15 focused hours/week; revise the schedule if actual availability differs.

Seven business tables: users, categories, products, customers, sales, sale_items, stock_movements. BDT is a fixed demonstration currency; quantities are whole units. No payments, tax, discounts, supplier procurement, warehouses, reservations, partial returns, or inventory valuation accounting. The receipt records a sale, not proof of payment.

## Actors and permissions

All authenticated active staff share this store's data. No per-clerk ownership filtering or multi-tenancy.

| Action | Manager | Sales clerk |
|---|---|---|
| View catalog, stock, customers, sales, reports, movement history | Yes | Yes |
| Create/edit customers; record sales | Yes | Yes |
| Create/edit/archive/restore categories and products | Yes | No |
| Receive stock or correct counts | Yes | No |
| Archive/restore customers | Yes | No |
| Cancel a completed sale; export inventory CSV | Yes | No |
| Edit/delete recorded sale items or movements | No normal feature | No |

Signed-out users can only reach login. Users are seeded/provisioned administratively; no registration or user-management UI. Actor comes from session identity, never a submitted user ID.

## Business rules

- BR-01: SKU is trimmed, uppercased ASCII, 1–64 characters from A–Z, 0–9, hyphen, underscore; unique even after archival. Product names need not be unique. Category names are trimmed lowercase Unicode, 1–80 characters, unique under the specified binary collation.
- BR-02: New products begin with zero stock. Opening balances are posted as restock movements. Direct stock editing is forbidden.
- BR-03: Stock is 0–1,000,000,000 units. Sale/restock quantities are 1–1,000,000. Corrections are nonzero signed deltas with absolute value at most 1,000,000. No operation may create negative stock or exceed the stock ceiling.
- BR-04: A submitted cart has 1–100 rows; merge duplicate product IDs, then validate each combined quantity and at most 100 distinct products. Conflicting expected prices for duplicate IDs are invalid.
- BR-05: Unit price is an exact decimal from 0.00 through 99,999,999.99, with at most two input fractional digits. Reject exponent notation, extra precision, NaN, and negative values. Store DECIMAL(10,2); return money as decimal strings.
- BR-06: The server locks products and uses their current prices. Client expected prices only detect changes: reject the whole request if any differs, asking the user to review and submit a new request key. Snapshot product name, SKU, and price into each sale item.
- BR-07: Sale header, items, stock deductions, and movements commit together. A failed line writes nothing from the sale. Totals are derived from quantity × snapshot price, not accepted from the client.
- BR-08: A cart is not a reservation. Sales have only completed -> cancelled states; no persisted draft or paid/unpaid state. Every completed/cancelled sale has at least one item, enforced by the service transaction.
- BR-09: Manager cancellation restores every item's quantity atomically, records actor/time/reason and one reversal per item. Retrying returns the same cancelled sale without another restoration. If any resulting stock exceeds its ceiling, roll back the entire cancellation and keep the sale completed.
- BR-10: Every stock-changing operation adds a movement on the same connection/transaction. Movement totals must reconcile to product stock. Movements and items are append-only through normal application features; database administrators can still alter data.
- BR-11: Restocks/corrections require a reason and an idempotency key. Same key and canonical payload returns the original result; changed payload is a conflict. Corrections also require expected product version, checked after detecting a valid retry.
- BR-12: Sale creation requires a unique request key and canonical payload fingerprint including actor, customer, sorted merged items, quantities, and expected prices. An exact retry returns the original sale, even if subsequently cancelled. Reusing the key with changed payload is a conflict.
- BR-13: Customer is optional; NULL means anonymous. Customer names are required; optional email/phone are not unique. Do not automatically merge people by name or email.
- BR-14: Product archival requires stock zero. Archived products cannot be sold or restocked. Cancellation can restore stock to an archived product without unarchiving it; a manager may correct its balance or restore it. History and inventory reports include it explicitly.
- BR-15: Archived categories cannot receive new/reassigned products; existing active products in them remain sellable. Archived customers cannot be selected for new sales. Archival does not delete history. Restoring a product does not require restoring its category; changing category does require an active target category.
- BR-16: Category/product/customer edits submit expected version. Stale writes fail with no change. Each successful edit/archive/restore increments version; each stock transaction increments each affected product version once. A no-op archive/restore is an idempotent no-op.
- BR-17: No physical delete UI or cascade deletion of business history. Users referenced by history are deactivated, not deleted.
- BR-18: Store DATETIME values in UTC; display Asia/Dhaka. Convert selected local calendar date ranges into UTC [start, end) boundaries. Stable ordering uses timestamp plus ID.
- BR-19: SKU/name/price snapshots preserve the facts shown at sale time. Customer links show the current customer record; no claim of immutable legal customer billing identity.
- BR-20: Canonical keys are UUID strings; fingerprints are SHA-256 of canonical validated fields, not raw JSON text. Format/field normalization must be identical on retries. Keys identify requests, not authorization.

## Functional requirements

| ID | Priority | Acceptance |
|---|---|---|
| FR-01 | Must | Login/logout using Laravel sessions/password hashing; active users only; generic failed-login response and throttling. |
| FR-02 | Must | Enforce the permission matrix and CSRF protection on direct requests, not only navigation controls. |
| FR-03 | Must | Manager category create/list/edit/archive/restore with normalization, duplicate handling, version conflicts. |
| FR-04 | Must | Manager product CRUD through archive/restore, SKU/category/price/threshold validation; stock only through dedicated operations. |
| FR-05 | Must | Customer create/list/edit; manager archival/restoration; optional customer on sale. |
| FR-06 | Must | Manager restock/correction with ledger, reason, limits, retry handling, correction version check. |
| FR-07 | Must | Multi-item sale with price review, stock locking, atomic commit and idempotency. |
| FR-08 | Must | Sale detail/receipt with immutable item snapshots, derived exact total, actor, timestamp, cancellation status. |
| FR-09 | Must | Manager full cancellation with reason, atomic once-only restoration and preserved history. |
| FR-10 | Must | Search/filter catalog, customers and sales; page size default 20, maximum 100; allowlisted sorts with ID tie-breaker. |
| FR-11 | Must | Reports below, including empty ranges, archived records, and timezone boundaries. |
| FR-12 | Must | Product movement timeline with reason, signed quantity, resulting stock, actor, timestamp, related sale where applicable. |
| FR-13 | Must | Read-only reconciliation report flags stock/ledger and sale/movement inconsistencies; never silently repairs them. |
| FR-14 | Should | Manager inventory CSV export with quoted fields and spreadsheet-formula neutralization. |
| FR-15 | Should | Small JavaScript enhancements; a separate React learning exercise once fundamentals pass. |
| FR-16 | Later | PostgreSQL migration and React integration preserving verified business behavior. |

## Reports

| Report | Definition |
|---|---|
| Inventory | Every product's current quantity; explicit active/archived filter. Stock is not monetary valuation. |
| Low stock | Active products where stock_on_hand <= reorder_level. Zero threshold includes zero stock. |
| Recorded sales value | Sum of item quantity × snapshot price for currently completed sales whose created_at lies in the range. Later cancellation removes the original sale from past-period results; label this retrospective operational metric. |
| Top products | Sum quantities/value grouped by product ID over that same completed-sale set. Do not join movements before summing. |
| Customer history | All linked sales including cancelled, with status visible. Anonymous sales are separate records, not one customer's history. |
| Movement report | All movements created in range, including reversals, regardless of current sale status. |
| Reconciliation | For every product, stock_on_hand = COALESCE(SUM(all deltas),0); additionally check sale/cancellation linkage, counts and signed quantities. |

## Use cases and errors

UC-01: manager creates a zero-stock product, posts an opening receipt, verifies ledger and stock. A clerk attempting the receipt gets forbidden.

UC-02: clerk submits cart and request key. The service claims the key, locks products in ascending ID order, checks active status/current price/available stock, writes all records, commits, returns receipt. Changed price, unavailable stock, or inactive customer rejects the whole cart. A lost response is safely retried with the same key.

UC-03: manager cancels a sale. Lock the header, then products in ascending ID order; restore and record reversals. A second cancellation returns existing state. A clerk cannot invoke this workflow.

UC-04: two clerks attempt the last unit. At most one sale commits; the other receives insufficient stock after acquiring the lock. Demonstrate with two independent database connections.

UC-05: two product editors load version 5. One commits version 6; the other gets a conflict. Restocking between load/save also invalidates that edit rather than losing the new stock.

## Nonfunctional requirements

| ID | Completion evidence |
|---|---|
| NFR-01 | Authentication, active-state checks, role/CSRF/actor spoofing/injection/XSS tests pass; restricted DB account and safe logs. |
| NFR-02 | Actual-engine constraint, concurrent-sale, cancellation, retry and injected rollback tests pass with zero ledger drift. |
| NFR-03 | Main path usable at 360px/1280px, keyboard only, labelled inputs, visible focus, clear error/empty/loading states. |
| NFR-04 | Target, not result: local warmed list/report p95 <= 1 second, <= 1% server errors; 10 warmups then 100 measured requests, 5 clients; fixture 1,000 products, 10,000 sales, 50,000 items. Record hardware, engine and query plans. |
| NFR-05 | Fresh migration/seed and backup/restore succeed in isolated databases using recorded versions. |
| NFR-06 | Laravel boundaries, reviewed migrations, lockfiles, formatting and critical workflow tests; documented commands work. |
| NFR-07 | Stock history retains actual actor and links through archival/cancellation; normal DB account cannot update/delete items/movements. |
| NFR-08 | Learner independently solves unseen SQL/FD/schedule exercises and explains sale transaction and its failure cases. |

## Change control

[SRS tests](TEST_PLAN.md) trace every requirement. Remove Should/Later work first if behind; any Must reduction needs an explicit SRS/test-plan amendment. The [review record](DOCUMENTATION_REVIEW.md) tracks external assumptions. Documentation is ready when contracts agree and remaining unknowns are explicit, not when it claims zero uncertainty.
