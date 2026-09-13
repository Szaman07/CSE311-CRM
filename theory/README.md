# CSE311 and full-stack learning guide

The examples target NexaStock's [implemented inventory schema](../docs/DATABASE_DESIGN.md); the existing root app/sql directories still contain the old CRM. Run inventory exercises only against the isolated NexaStock database.

This is a structured study companion, not a replacement for lectures, your rubric, or the textbook. Work problems before looking at answers. Prefer the instructor's notation and assigned chapters. Author materials: [Database System Concepts](https://www.db-book.com/).

| Order | Module | Evidence you should produce |
|---|---|---|
| 1 | [Relational model and algebra](01_relational_model_and_algebra.md) | Translate five questions into algebra and SQL; explain NULL/set differences |
| 2 | [ER design](02_erd_and_conceptual_design.md) | Draw cardinalities/participation; map M:N to keys |
| 3 | [FDs and normalization](03_functional_dependencies_and_normalization.md) | Closure, candidate keys, 3NF/BCNF, lossless/dependency-preserving proof |
| 4 | [Transactions and recovery](04_transactions_and_concurrency_control.md) | Schedule graphs, anomaly examples, rollback/redo explanation |
| 5 | [Storage and optimization](05_indexing_storage_and_query_optimization.md) | Page/index reasoning and before/after EXPLAIN |
| 6 | [Advanced SQL and security](06_advanced_sql_and_database_security.md) | View/routine exercises, least-privilege and threat tests |
| 7 | [Viva and mock exam](07_viva_defense_master_cheatsheet.md) | Timed unseen-style answers with reasons |
| 8 | [PHP, HTTP and Laravel](08_php_http_and_laravel.md) | One secure vertical feature you can trace |
| 9 | [JavaScript and React](09_javascript_and_react.md) | JS prerequisite tasks, then catalog/cart component exercise |
| 10 | [PostgreSQL migration](10_postgresql_migration.md) | Later parity checklist, not a second initial stack |
| Practice | [SQL workbook](11_sql_workbook.md) | Execute queries, predict results, change fixtures and explain |

## How to use the guide

Read one concept, attempt its exercise, check the worked answer, then solve a changed example unaided. Maintain an error log. Revisit weak topics after two days and a week. A query returning plausible data is insufficient: test zero rows, NULLs, duplicates, tied values and unexpected states.

Theory uses classical relations without NULL unless stated. SQL examples use bags and three-valued logic. Project-specific business rules are design decisions, not universal database laws. Advanced topics such as distributed databases, query-cost derivations or 4NF depth should follow the actual syllabus; use the textbook for full treatment.
