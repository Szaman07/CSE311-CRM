# 8. PHP, HTTP and Laravel from first principles

## Prerequisites in order

1. HTML document/forms: label/input/name, required, GET vs POST, accessibility.
2. PHP variables/types, arrays, loops, functions, exceptions, namespaces/classes, autoloading.
3. HTTP request/response: method, path, headers, body, status, redirect; browser doesn't connect directly to DB.
4. SQL via a parameterized query: observe bound values and returned rows in a disposable exercise.
5. Composer dependencies/autoloading and lockfiles; .env configuration vs committed source.
6. Laravel routing, middleware, controller, Form Request, policy, model/query, Blade rendering.
7. Transactions and feature tests, then JavaScript enhancements.

Build a tiny standalone PHP form in a scratch exercise first. Don't expand that scratch code into a second production backend. Use [PHP manual](https://www.php.net/manual/en/) and version-matched [Laravel documentation](https://laravel.com/docs/12.x).

## First vertical slice: category creation

Trace: browser POST -> web middleware/session/CSRF -> authenticated active-user check -> StoreCategoryRequest -> manager policy -> normalization/service -> INSERT category -> redirect -> escaped Blade output.

Test missing/blank/duplicate/oversized name, clerk request, expired CSRF, successful insert. Explain why a browser required attribute is bypassable. The DB unique constraint handles concurrent duplicates even if pre-validation found none.

Learn Eloquent belongsTo/hasMany and explicit fillable fields, but also write the generated query yourself. Avoid N+1 loops by fetching required relationships/aggregates intentionally. Do not build a generic repository layer before needing it.

## Authentication lab

Use Laravel Auth/Hash/session facilities. Seed a user with a locally provided password hashed through the framework. Valid login regenerates session ID; invalid login has a generic error; logout invalidates the session. No public registration. Check role/active state on protected routes; never trust posted role/user_id.

Explain cookies, HttpOnly, Secure, SameSite, CSRF, password hashing and authorization separately. Local HTTP and public HTTPS require different cookie configuration. Never commit real credentials.

## Transaction pseudocode

```php
// Teaching outline only: full validation/retry contract is in DATABASE_DESIGN.
DB::transaction(function () use ($input, $actor) {
    // Claim request key; lock each product in sorted order.
    // Recheck active state, current price, stock and numeric bounds.
    // Insert header/items; update stock/version; append movements.
    // Throw on failure. No rendering/network calls inside this closure.
});
```

Don't copy this as if it implements RecordSale. Write each stage, then add failure injection and two-connection tests.

## Readiness tasks

- Explain why .env must sit outside Apache's public root.
- Show which Form Request/policy/service rejects three distinct bad requests.
- Predict the SQL for a model relationship and verify query count.
- Rebuild category creation without generated code.
- Explain why migration DDL and business DML have different rollback expectations.

Pass these before introducing a React form library or more backend packages.
