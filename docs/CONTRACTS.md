# Routes, validation, and response contracts

Implemented Laravel browser application and `/api/v1` adapter. [SRS](SRS.md) owns permissions and [OpenAPI](openapi.yaml) owns the HTTP description. [RecordSale JSON Schema](contracts/record-sale.schema.json) and [example](contracts/record-sale.example.json) describe the **normalized service input**, not literal HTML form encoding. Form Requests validate transport input; services canonicalize the allowlisted domain fields.

IDs travel as decimal strings in responses. Writable IDs and versions must fit PHP's signed 64-bit integer range (1–9,223,372,036,854,775,807); the database's unsigned BIGINT storage range is wider. Numeric ID input is accepted for compatibility, but JavaScript callers should use strings. Booleans, floats, exponent notation, and out-of-range whole numbers are rejected before casts. Version responses, money, and unbounded report aggregates are decimal strings; report timestamps are ISO UTC strings. Product price input accepts up to two fractional places and is normalized; sale expected prices use exactly two.

## Route map

| Method/path | Input | Actor / result |
|---|---|---|
| GET/POST /login; POST /logout | email/password; CSRF | Public login, authenticated logout; session lifecycle |
| GET /dashboard | none | Staff; summary |
| GET /categories; POST /categories | name for create | Staff list; manager create |
| PATCH /categories/{id} | name, expected_version | Manager; update |
| POST /categories/{id}/archive or /restore | expected_version | Manager; state action |
| GET /products; GET /products/{id}; POST /products | category_id, sku, name, unit_price, reorder_level | Staff read; manager create (stock forced zero) |
| PATCH /products/{id} | category_id, sku, name, unit_price, reorder_level, expected_version | Manager; no stock field |
| POST /products/{id}/archive or /restore | expected_version | Manager |
| POST /products/{id}/restocks | request_key, quantity, note | Manager |
| POST /products/{id}/adjustments | request_key, quantity_delta, expected_version, note | Manager |
| GET /customers; GET /customers/{id}; POST /customers | full_name, optional email/phone | Staff |
| PATCH /customers/{id} | full_name, email/phone, expected_version | Staff |
| POST /customers/{id}/archive or /restore | expected_version | Manager |
| GET /sales/create; POST /sales | request_key, customer_id, items | Staff; receipt after commit |
| GET /sales; GET /sales/{id} | optional filters for list | Staff; status/snapshot detail |
| POST /sales/{id}/cancel | reason (1–500 trimmed characters) | Manager; repeated cancellation returns existing result |
| GET /products/{id}/movements | pagination | Staff |
| GET /reports/{report} | allowlisted report/filter names | Staff; no arbitrary SQL/report path |
| GET /reports/inventory.csv | active/archived filter | Manager; Should requirement |

All mutations use CSRF middleware. Blade PATCH forms use Laravel method spoofing; GET never mutates. Route IDs don't authorize access. Report paths are an explicit route allowlist, not dynamic file includes. No actor/status/total/stock/fingerprint from client input is accepted.

## Canonical request and idempotency

Canonical sale object has actor_id from session, customer_id or NULL, and items sorted by numeric product ID after merging duplicates. UUID keys are lowercase canonical text; money has two fractional places. JSON property order is fixed; UTF-8 encoding is specified. Fingerprint the canonical object using SHA-256; store the key separately.

Retry only with unchanged original fields. If user changes cart, price or customer, generate a new key. Keep the same key during network/timeout retry. A rejected uncommitted sale has no persistent claim, but use a new key after editing its payload. Never use fingerprint equality to bypass authentication.

Manual stock canonical object includes actor_id, product_id, operation, quantity/delta, normalized note and expected_version for corrections. Exact retries return original movement ID and recorded resulting_stock (not a promise of current stock after later operations).

## Structured outcomes

An initial sale returns HTTP 201 when JSON is requested:
```json
{"data":{"id":"42","status":"completed","total":"70.00","currency":"BDT"},"replayed":false}
```

Exact retry: HTTP 200, same sale ID, replayed true; current cancellation state is included. Browser success uses Laravel's 302 redirect to the receipt. Cancellation/restock success redirects to sale/product detail. Errors do not contain SQL, stack traces, password hashes, or tokens. Database exception logs include only request ID and engine error codes, excluding SQL bindings.

| Condition | JSON response | Blade behavior |
|---|---|---|
| Missing login | 401 | Redirect to login |
| Forbidden/disabled account | 403 | Safe forbidden/session-ended page |
| Missing record | 404 | Not-found page |
| Invalid shape/field/quantity | 422 with field errors | Redirect back with nonsecret input and errors |
| Stale edit, changed price, insufficient stock, key mismatch, cancellation ceiling | 409 with stable code | Conflict explanation; preserve cart for review |
| CSRF token expired/invalid | Laravel's configured CSRF failure response (commonly 419) | Reload/login guidance; no write |
| Retryable DB contention exhausted | 409, code retry_later | Retry prompt preserving original key |
| Unexpected internal error | 500, request_id | Generic failure and request ID |

Example conflict: {"error":{"code":"insufficient_stock","context":{"product_id":"1","stock_on_hand":0},"message":"A product does not have enough stock."}}. Only disclose store data to active staff. Errors can include the latest authorized quantity/price, but client must review before submitting a changed request.

## Shared validation

Names and notes use their DDL lengths; required strings cannot be whitespace. Email is format-checked; blank optional email/phone becomes NULL. Write requests reject unknown fields, including actor and direct-stock fields; Blade's `_token` and `_method` are transport metadata. No uploads or external product URLs in core. SKU and category normalization are defined in DATABASE_DESIGN. Search text max 100 characters; page 1–1,000,000, per_page 1–100 (default 20); sorts mapped to fixed columns. Invalid arrays, filters, and page bounds return validation errors. Parameter binding covers values, not arbitrary SQL identifiers.

Date filters use valid calendar dates with start <= end, checked after applying the defaults (today minus 29 days through today in Dhaka). UI end date is inclusive; convert to midnight of the following local day then UTC for exclusive SQL upper bound. Export cells are properly CSV-quoted and text fields beginning with =, +, -, @ or leading control/whitespace followed by those are neutralized; test the exported artifact in the intended spreadsheet tool.

Top products returns one row per product ID, labelled with its current name/SKU. Units and recorded value are calculated from completed-sale snapshots; renaming a product does not split its totals or rewrite receipts. API aggregate counts/balances are strings when their sum can exceed an individual row's bounds.
