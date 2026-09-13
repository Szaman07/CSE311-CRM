# 5. Storage, indexes and query optimization

## Storage model

Rows live on pages; page reads/writes and buffer-cache behavior matter. Heap and clustered organization affect access paths. InnoDB organizes table rows by clustered primary key; secondary indexes include the primary key, so wide keys enlarge secondary storage. Logical index descriptions don't make every engine identical.

B+ trees keep search keys in internal pages and entries at leaves; high fanout keeps height small and linked leaves support ranges. Hash indexes suit equality but do not naturally support ordered range scans. Do not assume CREATE INDEX creates a hash index on InnoDB.

Teaching estimate: fanout f and N entries imply height roughly logarithmic in N, but exact occupancy, root convention and cached levels matter. There is no universal “maximum four I/Os.” A secondary lookup can require tree traversal plus clustered lookups for many rows.

## Composite indexes and selectivity

Index (status,created_at,id) suits equality on status followed by a date range and stable ordering. It doesn't guarantee the best access path for every date-only query. Leftmost prefix is a useful design rule; optimizer exceptions and scans still exist.

A covering index contains all values needed for a query, potentially avoiding base-row fetches. It costs space/writes and can be too wide. Index every column is not a strategy. Boolean/low-selectivity filters may not justify an index alone.

Sargability: compare created_at >= :start AND created_at < :end rather than wrapping every row in DATE(created_at), so a normal date index can be useful. LIKE 'pen%' may use an appropriate index; LIKE '%pen%' typically cannot use a simple B-tree range seek.

## Joins and plans

Nested-loop join probes inner data for each outer row; an appropriate inner key helps. Hash/merge join availability and chosen plans vary by engine/version. Optimization uses statistics and cost estimates; estimates can be wrong because of skew, correlation or stale stats.

For NexaStock, compare EXPLAIN for completed sales in a date range before/after the proposed index in an isolated experiment. Record access type, chosen key, estimated rows and join order. Runtime-plan commands differ between MariaDB/MySQL/PostgreSQL; consult the installed engine docs before issuing them. Some ANALYZE variants execute the query.

Pre-aggregate independent children before joining. An index cannot repair wrong aggregation semantics.

## Lab procedure

1. Use a generated fixture with known distribution, not four rows.
2. Write correct query and calculate a small-fixture expected result.
3. Capture EXPLAIN and repeated warmed timings.
4. Add one justified index through a test migration; capture changed plan/timing.
5. Report read improvement and write/storage cost; remove it if unjustified.
6. Run [performance protocol](../docs/TEST_PLAN.md) with recorded machine/version.

## Worked reasoning

A category has 3 products and 4 independent notes in a hypothetical extra table. Joining both children before COUNT(*) yields 12 rows. COUNT(DISTINCT product.id) can correct product count; SUM(DISTINCT price) fails if two products share a price. Pre-aggregate facts at the intended grain.

Exercise: Why might a full scan beat an index for 90% of a small table? **Answer:** sequential/cache-local access can be cheaper than many index and row lookups; selectivity, width, cache and optimizer estimates matter.

Gate: explain a real plan and its limitations; don't claim “optimized” from index existence alone.
