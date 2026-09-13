# Frontend integration contract

This document describes the shipped `/api/v1` adapter. The normative machine contract is [openapi.yaml](openapi.yaml); Laravel routes, Form Requests, resources, and domain services are the executable implementation. Blade and JSON call the same services.

## Browser bootstrap and authentication

The current client is same-origin. First `GET /login` and retain the session cookie. Read the `csrf-token` meta element, then send its value as `X-CSRF-TOKEN` on `POST /api/v1/login`. Successful login regenerates the session and returns the rotated `data.csrf_token`; use it for later POST/PATCH requests. `GET /api/v1/me` restores identity and returns the current token; `POST /api/v1/logout` invalidates the session. Fetch must use `credentials: "same-origin"`. The working helper is `inventory/resources/js/nexa-api.js`.

There is no JWT, public registration, remember-me, database credential, trusted actor ID, stock setter, or client total. A future separately served first-party React SPA should use Laravel Sanctum's cookie flow only after configuring stateful first-party domains, credentialed CORS, HTTPS cookie domain/SameSite settings, and `/sanctum/csrf-cookie`. That setup is a later milestone and has not been verified.

```js
import { api, newRequestKey } from './nexa-api';

const sale = await api('/api/v1/sales', {
  method: 'POST',
  body: JSON.stringify({
    request_key: newRequestKey(),
    customer_id: null,
    items: [{ product_id: 1, quantity: 2, expected_unit_price: '10.00' }],
  }),
});
```

## Representation and recovery rules

- IDs are base-10 strings in responses, even though current inputs accept positive integers. Treat IDs as opaque strings in React.
- BDT amounts are fixed two-decimal strings. Never parse them through binary floating point for equality or totals.
- API times are ISO 8601 with an offset. Stored `DATETIME` values are UTC; report `start`/`end` are Asia/Dhaka calendar dates converted to a half-open UTC interval.
- Collections default to 20 rows and cap `per_page` at 100. Order always has an ID tie-breaker.
- Product, category, and customer mutations carry `expected_version`. On `version_conflict`, reload and ask the user to review.
- Generate a UUID with `crypto.randomUUID()` once for a sale, receipt, or correction. Keep that key and the unchanged canonical payload after an ambiguous timeout. If the user changes the customer, line, quantity, expected price, reason, or expected version, generate a new key.
- `201` means a sale/movement was first created. `200` plus `replayed: true` means the committed original was returned. A sale replay can correctly return `status: "cancelled"`.
- On `price_changed`, show the current server price and require review. Never silently rewrite the cart. On `insufficient_stock`, refresh the catalog. On a 419, reload CSRF/session state before deciding whether to retry the same idempotency key.

All API failures use `error.code`, a safe `error.message`, and a correlation `request_id` where middleware ran. Validation adds `error.fields`; conflicts can add safe `error.context`. Implemented codes include `unauthenticated`, `invalid_credentials`, `account_inactive`, `session_expired`, `forbidden`, `resource_not_found`, `validation_failed`, `rate_limited`, `csrf_failed`, `version_conflict`, `price_changed`, `insufficient_stock`, `stock_bounds`, `stock_not_zero`, `category_inactive`, `customer_inactive`, `product_inactive`, `idempotency_conflict`, `integrity_error`, and `internal_error`.

## Permissions

Both active roles can view shared store data, create/edit customers, and record sales. Managers additionally manage catalog/archive state, receive/correct stock, cancel sales, and export inventory CSV. The server resolves the actor from the session and ignores no unrestricted `created_by`, total, stock, status, cancellation actor, or archive timestamp fields because those fields are absent from request schemas.

## Route groups

The OpenAPI document covers login/logout/me; categories; products and archive state; receipts/corrections/movements; customers and linked sales; searchable sales, receipt detail, cancellation; five reports; and manager CSV. No generic editing endpoint exists for sale items or movements.
