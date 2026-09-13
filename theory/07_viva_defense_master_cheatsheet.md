# 7. Viva preparation and timed practice

Use this after studying, not as a script to memorize. Say what the **implemented** system does; distinguish the documented target until you have test evidence.

## Defense questions with answer anchors

| Question | What a sound answer must include |
|---|---|
| Why inventory? | Concrete M:N sales/items, constraints, stock concurrency, ledger reconciliation; manageable one-store scope |
| Why Laravel and XAMPP? | Course runtime requirement; framework conventions; measured version compatibility; SQL still studied directly |
| Is this MySQL or MariaDB? | Actual SELECT VERSION output; don't infer from control-panel label |
| What are candidate keys? | Minimal superkeys under declared FDs; product id and normalized SKU examples |
| Why an item table? | M:N resolution, consolidated product per sale, snapshot facts |
| Is every table 3NF? | Conditional FD analysis; movement product redundancy and cached balances explicitly acknowledged |
| Why snapshot prices? | Historical sale fact survives later catalog price change |
| Why store stock and movements? | Fast guarded availability plus explanation; transaction maintenance and reconciliation obligation |
| What prevents oversell? | Product lock in one transaction, current balance check, atomic writes, two-session evidence |
| What if response is lost? | Same canonical request/key returns committed sale without new deduction |
| What does UNIQUE guarantee? | At most one key value/pair; not required child existence |
| What happens on cancellation? | Locked header, once-only all-item reversal, ceiling checks, preserved snapshots |
| Why not call the total revenue? | Recorded operational sale value; no payment/accounting subsystem |
| What do archived products do? | Not sold/restocked; history retained; cancellation may restore archived stock visibly |
| Can a clerk forge manager actions? | Server policy, session actor, direct-request tests |
| Is FOR UPDATE the whole isolation story? | MVCC/lock/isolation distinctions; other paths and cross-row invariants matter |
| What is lossless decomposition? | Join reconstructs exactly legal original relation under F; prove intersection criterion for binary case |
| Does an index always help? | Cost/selectivity/write overhead; explain measured plan |
| How would PostgreSQL differ? | Dialects/types/collation/isolation/errors and migration/parity tests |
| What did you write independently? | Honest commit/practice evidence; explain one transaction/query without assistance |

## 45-minute practice paper

1. (8 marks) Write SQL for active products never sold in a completed sale, including never-used products.
2. (8) R(A,B,C,D), F={A->B, B->C, A->D}. Find keys, normal form and a lossless dependency-preserving decomposition.
3. (8) Analyze r1(X), r2(X), w1(X), w2(X), c1, c2 for conflict serializability. Draw edges.
4. (8) Design a last-unit two-connection test with expected rows/stock and retry behavior.
5. (8) A sale has two equal-price items and each has two movements. Explain a naive SUM fanout and repair.
6. (5) Distinguish authentication, authorization and CSRF using one endpoint.

## Worked outline — inspect only after attempting

1. Products active filter + correlated NOT EXISTS items JOIN sales WHERE status='completed'; no NOT IN NULL trap.
2. A only candidate; 2NF but not 3NF due B->C. AB, BC, AD; B determines BC at first join, A determines AD at second; dependencies local.
3. 1->2 from r1/w2 and w1/w2; 2->1 from r2/w1. Cycle, not conflict-serializable.
4. Start stock1; pause first after lock; second waits; first commits1sale, second rejects; final0, one item/deduction. Same-key retry returns that sale.
5. Movements multiply each item's amount; aggregate item values at sale grain without joining movements, or separately aggregate movements. DISTINCT amount incorrectly drops legitimate equal values.
6. Session login establishes user; policy permits manager cancellation; CSRF validates intended session-bound mutation origin.

Score explanations and counterexamples, not keywords. Any weak topic gets a fresh question two days later. Replace these examples with unseen past-paper questions before judging exam readiness.
