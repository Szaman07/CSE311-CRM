# 1. Relational model, algebra and SQL semantics

## Concepts

A relation schema gives attributes and domains; an instance is its current set of tuples. Degree is number of attributes; cardinality is number of tuples. Classical relations have no duplicate tuples or inherent row order. SQL SELECT normally returns a bag; DISTINCT removes duplicates and ORDER BY explicitly orders output.

A superkey uniquely identifies a tuple. A candidate key is a minimal superkey; primary key is the chosen candidate. A foreign key constrains references but does not mean each parent has a child. Names and sample-data uniqueness do not prove keys.

NULL means missing/unknown/inapplicable information, not zero or an empty string. SQL predicates can be TRUE/FALSE/UNKNOWN; WHERE keeps TRUE. Use IS NULL. COUNT(*) counts rows; COUNT(column) excludes NULL values. SUM over no input rows gives NULL, so use COALESCE where zero is the intended result.

## Operators with inventory examples

| Operator | Meaning | Example |
|---|---|---|
| Selection sigma | Choose rows | products whose stock_on_hand <= reorder_level |
| Projection pi | Choose attributes, remove duplicates in classical algebra | product category IDs |
| Rename rho | Rename relation/attributes | two copies of products for a self join |
| Product × | All row pairs | categories × products before matching |
| Theta join | Product followed by condition | categories.id = products.category_id |
| Union/intersection/difference | Combine compatible sets | active product IDs minus sold product IDs |
| Division | Match every member of a required set | customers buying from every required category |

Extended algebra supplies aggregation and outer joins. NATURAL JOIN can accidentally match newly added same-name columns; use explicit keys in application SQL.

Example: product names in active categories:
pi products.name (sigma categories.archived_at IS NULL (products JOIN products.category_id=categories.id categories)).
In classical algebra use an explicit active attribute rather than importing SQL NULL semantics without explanation.

## Translate carefully

```sql
SELECT DISTINCT p.category_id FROM products p;
SELECT p.id, p.name
FROM products p
WHERE NOT EXISTS (
 SELECT 1 FROM sale_items i JOIN sales s ON s.id=i.sale_id
 WHERE i.product_id=p.id AND s.status='completed'
);
```

The second query includes never-sold products and those appearing only in cancelled sales. Explain that choice before running it.

For customers buying from **every active category**, take customers as candidate universe and active categories as required universe. Use nested NOT EXISTS: there must not exist a required category lacking a completed purchase by that customer. If the required universe is empty, every customer qualifies by vacuous truth. Choosing categories observed in sales would change the question and hide never-purchased categories. A complete query is in the [workbook](11_sql_workbook.md).

## Exercises and worked checks

1. Category A has two products. Does SELECT category_id return one or two A rows? **Two** without DISTINCT; classical projection returns one.
2. A LEFT JOIN produces one NULL-extended row for a category with no products. COUNT(*) vs COUNT(p.id)? **1 vs 0**.
3. Why can NOT IN fail if its subquery returns NULL? For a nonmatching value, comparison against NULL contributes UNKNOWN, so WHERE does not keep it. Use a properly correlated NOT EXISTS or exclude NULL deliberately.
4. Are WHERE price > 10 and WHERE NOT(price <= 10) different for NULL? Both yield UNKNOWN and exclude it. NOT UNKNOWN is still UNKNOWN.
5. Is row order stable without ORDER BY because IDs increased? **No.** Storage/access plans do not establish a result-order contract.

Gate: predict exact rows for a fixture with duplicates, zero-child categories and NULL customers before executing SQL.
