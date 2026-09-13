# 9. JavaScript, then React

Your lack of React experience is accounted for: the first useful application renders with Blade. Learn browser behavior in small exercises; React is a later interface milestone, not a prerequisite for relearning SQL.

## JavaScript foundations

Learn let/const, values and coercion, functions, arrays/objects, map/filter/reduce, destructuring, modules, DOM/events, form submission, promises/async-await, fetch and error handling. Use [MDN learning material](https://developer.mozilla.org/en-US/docs/Learn_web_development).

Exercises:
1. Filter an in-memory product array by name and low-stock status.
2. Merge duplicate cart product IDs and reject combined quantities over the limit.
3. Render results with textContent rather than injecting untrusted HTML.
4. Fetch a read-only catalog and handle loading, empty, HTTP error and network error separately; fetch doesn't reject merely because HTTP status is 422.
5. Keep product IDs and exact prices as strings. For a small preview, use carefully bounded integer minor units; authoritative totals still come from the server. Large legal project totals exceed safe JS integer precision.
6. Submit once with a key; simulate lost response and retry the unchanged payload with the same key. Disable pending submit for UX, but explain why server idempotency is still required.

Gate: complete these and explain them without relying on a library before React.

## React progression

Follow [React Learn](https://react.dev/learn): components/JSX, props, state, event handlers, lists/keys, controlled inputs, lifting state and effects for external synchronization. Updating arrays/objects requires new state references; don't mutate state in place. Derived filtered lists/totals often don't need duplicate state or effects.

Build a read-only ProductTable -> SearchBox -> ProductRow exercise with mock data. Then a Cart where product IDs are stable keys, quantity is validated, pending/error state is explicit and server price conflicts ask for review.

Don't place DB credentials or SQL in browser code. React doesn't enforce backend permissions or replace transactions. A stale browser view doesn't reserve stock.

## Integration milestone

After the coursework release, use React with Inertia in the existing Laravel app so routes/auth/services remain intact. Learn TypeScript basics (object shapes, unions, narrowing, nullable fields) when integrating. Version-match Inertia/React packages at implementation time; don't mix tutorials from unrelated releases.

Keep Blade critical flows until replacement parity tests pass, then deliberately remove duplicate UI paths. PostgreSQL migration is a separate milestone. Avoid changing database, auth system and frontend together because failures become hard to isolate.

## Reflection questions

What belongs in state? Which values are derived? What happens if stock changes after render? Why does a client-generated total not authorize a price? Why do retries need a stable key? Demonstrate answers with a failing request, not only a happy-path screenshot.
