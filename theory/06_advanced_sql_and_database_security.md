# 6. Advanced SQL, views, routines and security

## Views, CTEs and windows

A normal view stores a query definition, not a precomputed result cache. Materialized views are engine-specific. Updateability depends on engine and query structure; a join view isn't universally writable or universally forbidden. Aggregate totals views are used read-only in this project.

A CTE names a query expression; it doesn't inherently make SQL faster. Window functions calculate over a partition while retaining rows, unlike GROUP BY collapse. Specify the intended frame for running totals.

```sql
SELECT product_id, id, quantity_delta,
 SUM(quantity_delta) OVER (
  PARTITION BY product_id ORDER BY id
  ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
 ) AS calculated_stock
FROM stock_movements;
```

Compare calculated_stock with resulting_stock using an outer query/CTE, not the window alias in the same SELECT's WHERE. This exercise requires a supported window-function engine version.

## Stored routines and triggers

A procedure exposes a database-side operation; a function returns a value under engine-specific restrictions. A trigger responds to a table event. They can centralize rules but also create hidden side effects and dialect-specific migration work.

The baseline uses Laravel services to own stock transactions. Do **not** add a stock-deducting trigger alongside RecordSale; that would deduct twice. If a rubric requires a routine/trigger, add a separate reviewed learning migration in a disposable schema first. A suitable lab trigger rejects UPDATE/DELETE of stock_movements using the engine's error mechanism; explain its limits and overlap with runtime grants. A suitable procedure lab is a parameterized read-only low-stock report. Adopt neither silently.

DDL may implicitly commit in MySQL-family systems. Do not assume schema changes roll back like ordinary InnoDB DML.

## Security layers

Authentication establishes identity; authorization decides allowed action; validation checks accepted shape/meaning. Prepared parameters separate data values from SQL text. They do not secure dynamically concatenated ORDER BY/table names; map those to an allowlist.

CSRF protection defends session-authenticated mutations from unwanted cross-site requests; it does not stop authorized users submitting invalid business inputs. Escape HTML output to mitigate XSS. A password hash differs from encryption; never log plaintext passwords. Database privileges are separate from application roles: a shared DB login can't automatically distinguish manager/clerk.

Use separate migration/runtime accounts and deny runtime DDL/DELETE; items/movements allow SELECT/INSERT only. The reference views use SQL SECURITY INVOKER. Know whether a view executes with caller or definer privileges before treating it as a security boundary.

## Exercises and answers

1. Is prepared SQL “100% secure”? **No:** authorization, credential handling, CSRF, XSS, identifier concatenation and business rules remain.
2. Can UNIQUE(sale_item_id,reason) guarantee a deduction exists? **No:** at most one for a non-null pair; the service must create it.
3. Does stock >=0 imply no oversell? **No:** two sales can both record units and overwrite stock with the same nonnegative number if the workflow is wrong.
4. Is hiding manager buttons enough? **No:** submit the direct request as a clerk and test policy rejection.
5. Why read-only reconciliation? **Answer:** a difference doesn't tell which fact is wrong. Silent overwrite erases evidence. Investigate and post an explicit authorized correction if appropriate.

Gate: demonstrate one attack/request per security layer and show which test proves rejection.
