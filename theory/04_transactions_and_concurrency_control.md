# 4. Transactions, schedules, isolation and recovery

## ACID through a sale

Atomicity: header/items/deductions/movements all commit or none. Consistency: declared invariants remain true **if** constraints and transaction logic are correct; ACID doesn't invent business rules. Isolation: concurrent execution follows the selected isolation guarantees. Durability: committed results survive supported failures under the engine's logging/storage configuration, not every imaginable disaster.

A transaction isn't just a collection of SQL statements. All tables must use a transactional engine and all statements must use the same connection/transaction owner.

## Schedules and serializability

Two operations conflict if they are from different transactions, access the same item, and at least one writes. Build a precedence graph: edge Ti -> Tj when Ti's conflicting operation precedes Tj's. Acyclic iff conflict-serializable; a topological order gives an equivalent serial order. Serializability concerns observable reads/writes, not merely a coincidentally equal final balance.

Example r1(X), r2(X), w1(X), w2(X): r1 before w2 gives 1->2; r2 before w1 gives 2->1. Cycle, not conflict-serializable. Both reading stock 1 then writing stock 0 can record two sales for one unit.

Recoverability is separate. If T2 reads T1's write, recoverability requires T1 commit before T2 commits. Cascadeless schedules allow reads only of committed writes. Strict schedules prevent others reading or overwriting a transaction's writes until it completes. Consider aborted transactions too when studying recovery.

Example w1(X), r2(X), c2, a1 is nonrecoverable. w1(X), r2(X), a1, a2 illustrates cascading aborts.

## Locks and actual engine behavior

Two-phase locking: acquire locks in growing phase, release in shrinking phase; after release, acquire no new locks. Strict 2PL conventionally holds exclusive locks until transaction end; rigorous 2PL holds shared and exclusive locks. Follow the course's terminology.

InnoDB combines locks and MVCC. SELECT ... FOR UPDATE in a transaction is a locking read; an ordinary SELECT may read a snapshot and should not be described as blocked by every write lock. One FOR UPDATE statement does not establish that the whole application uses pure strict 2PL.

Project strategy: lock products individually by ascending primary key, validate current stock, write and commit. Cancellation locks sale first then products. Uniform ordering reduces deadlocks; FKs/indexes can still cause contention. Retry an entire rolled-back safe transaction, bounded, with the same idempotency key.

## Isolation anomalies

Dirty read: observe uncommitted data. Nonrepeatable read: same row changes between reads. Phantom: predicate result membership changes. Lost update: one write overwrites another's effect. Write skew: concurrent decisions over a shared invariant update different rows, potentially both committing under snapshot isolation.

Example write skew: two on-call doctors each see two available and each independently marks self unavailable. Row locks only on each doctor's own row don't protect the cross-row invariant. Lock the full relevant set/guard row or use a suitable serializable design.

Engine labels do not imply identical implementation behavior. Test actual isolation, locking and retry behavior. [InnoDB error handling](https://dev.mysql.com/doc/refman/8.4/en/innodb-error-handling.html)

## Recovery essentials

Write-ahead logging requires relevant log records to reach durable storage before corresponding dirty data pages. Commit durability requires the appropriate commit log flush under configuration. Steal can allow uncommitted pages to disk, motivating UNDO; no-force can leave committed changes only in log at commit, motivating REDO. Checkpoints reduce recovery work; they are not backups.

A pedagogical log: <T1 start>, <T1 X old=10 new=8>, <T1 commit>. Crash before data-page flush can need redo. Without a durable commit, recovery may need undo of persisted uncommitted changes. ARIES analysis/redo/undo, LSNs and compensation log records are textbook extensions; do not pretend a hand example is a complete InnoDB implementation.

Backup/restore protects against broader data loss than transaction rollback. Test restoring into a separate DB and running reconciliation.

## Practice gate

Given r1(X), w1(X), r2(X), w2(Y), c1, c2:
Graph has 1->2 only: conflict-serializable. T2 reads uncommitted X, so not cascadeless; commits after T1, so recoverable. Explain all three independently. Then perform the actual two-connection last-unit test in [TEST_PLAN](../docs/TEST_PLAN.md).
