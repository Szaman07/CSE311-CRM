<?php

namespace App\Services;

use App\Domain\DomainConflict;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Canonical;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SaleService
{
    use GuardsDomain;

    public function record(User $actor, ?int $customerId, array $submittedItems, string $requestKey): Sale
    {
        $this->requireActive($actor);
        $requestKey = strtolower(trim($requestKey));
        if (! Str::isUuid($requestKey)) {
            throw new DomainConflict('invalid_request', 'A canonical UUID request key is required.');
        }

        $items = $this->canonicalItems($submittedItems);
        $canonical = [
            'actor_id' => (string) $actor->id,
            'customer_id' => $customerId === null ? null : (string) $customerId,
            'items' => array_map(fn (array $i): array => [
                'product_id' => (string) $i['product_id'],
                'quantity' => $i['quantity'],
                'expected_unit_price' => $i['expected_unit_price'],
            ], $items),
        ];
        $fingerprint = Canonical::fingerprint($canonical);

        if ($existing = Sale::where('request_key', $requestKey)->first()) {
            return $this->resolveReplay($existing, $actor, $fingerprint);
        }

        try {
            return DB::transaction(function () use ($actor, $customerId, $items, $requestKey, $fingerprint): Sale {
                if ($customerId !== null) {
                    $customer = Customer::lockForUpdate()->findOrFail($customerId);
                    if ($customer->archived_at) {
                        throw new DomainConflict('customer_inactive', 'Choose an active customer.');
                    }
                }

                $sale = Sale::create([
                    'customer_id' => $customerId,
                    'created_by' => $actor->id,
                    'request_key' => $requestKey,
                    'request_fingerprint' => $fingerprint,
                    'status' => 'completed',
                ]);

                $lockedProducts = [];
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    if (! $product) {
                        throw new DomainConflict('product_missing', 'A selected product no longer exists.', ['product_id' => (string) $item['product_id']]);
                    }
                    if ($product->archived_at) {
                        throw new DomainConflict('product_inactive', 'A selected product is archived.', ['product_id' => (string) $product->id]);
                    }
                    if (! hash_equals(Canonical::money((string) $product->unit_price), $item['expected_unit_price'])) {
                        throw new DomainConflict('price_changed', 'A product price changed. Review the cart.', ['product_id' => (string) $product->id, 'current_price' => Canonical::money((string) $product->unit_price)]);
                    }
                    if ($product->stock_on_hand < $item['quantity']) {
                        throw new DomainConflict('insufficient_stock', 'A product does not have enough stock.', ['product_id' => (string) $product->id, 'stock_on_hand' => $product->stock_on_hand]);
                    }
                    $lockedProducts[$product->id] = $product;
                }

                foreach ($items as $item) {
                    $product = $lockedProducts[$item['product_id']];
                    $line = SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => Canonical::money((string) $product->unit_price),
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                    ]);
                    $newStock = $product->stock_on_hand - $item['quantity'];
                    $product->stock_on_hand = $newStock;
                    $product->version++;
                    $product->save();
                    StockMovement::create([
                        'product_id' => $product->id,
                        'sale_item_id' => $line->id,
                        'quantity_delta' => -$item['quantity'],
                        'resulting_stock' => $newStock,
                        'reason' => 'sale',
                        'created_by' => $actor->id,
                        'created_at' => now(),
                    ]);
                }

                return $sale->load(['customer', 'creator', 'items']);
            }, 3);
        } catch (QueryException $e) {
            if ($this->isRequestKeyCollision($e) && ($existing = Sale::where('request_key', $requestKey)->first())) {
                return $this->resolveReplay($existing, $actor, $fingerprint);
            }
            throw $e;
        }
    }

    public function cancel(User $actor, Sale $sale, string $reason): Sale
    {
        $this->requireManager($actor);
        $reason = Canonical::text($reason);

        return DB::transaction(function () use ($actor, $sale, $reason): Sale {
            $lockedSale = Sale::lockForUpdate()->findOrFail($sale->id);
            if ($lockedSale->status === 'cancelled') {
                return $this->loadSale($lockedSale);
            }
            if ($lockedSale->status !== 'completed') {
                throw new DomainConflict('invalid_state', 'Only a completed sale can be cancelled.');
            }

            $items = SaleItem::where('sale_id', $lockedSale->id)->orderBy('product_id')->get();
            if ($items->isEmpty()) {
                throw new DomainConflict('integrity_error', 'The sale has no items and cannot be safely cancelled.');
            }

            foreach ($items as $item) {
                $deductions = StockMovement::where('sale_item_id', $item->id)->where('reason', 'sale')->get();
                if ($deductions->count() !== 1 || $deductions->first()->quantity_delta !== -$item->quantity) {
                    throw new DomainConflict('integrity_error', 'Sale movement history is inconsistent.', ['sale_item_id' => (string) $item->id]);
                }
            }

            $products = [];
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                $restored = $product->stock_on_hand + $item->quantity;
                if ($restored > 1_000_000_000) {
                    throw new DomainConflict('stock_bounds', 'Cancellation would exceed the stock ceiling.', ['product_id' => (string) $product->id]);
                }
                $products[$product->id] = [$product, $restored];
            }

            foreach ($items as $item) {
                [$product, $restored] = $products[$item->product_id];
                $product->stock_on_hand = $restored;
                $product->version++;
                $product->save();
                StockMovement::create([
                    'product_id' => $product->id,
                    'sale_item_id' => $item->id,
                    'quantity_delta' => $item->quantity,
                    'resulting_stock' => $restored,
                    'reason' => 'cancellation',
                    'created_by' => $actor->id,
                    'note' => $reason,
                    'created_at' => now(),
                ]);
            }

            $lockedSale->status = 'cancelled';
            $lockedSale->cancelled_by = $actor->id;
            $lockedSale->cancelled_at = now();
            $lockedSale->cancel_reason = $reason;
            $lockedSale->save();

            return $this->loadSale($lockedSale);
        }, 3);
    }

    public function total(Sale $sale): string
    {
        $items = $sale->relationLoaded('items') ? $sale->items : $sale->items()->get();
        $cents = 0;
        foreach ($items as $item) {
            $cents += Canonical::cents((string) $item->unit_price) * $item->quantity;
        }

        return Canonical::formatCents($cents);
    }

    private function canonicalItems(array $submitted): array
    {
        if (count($submitted) < 1 || count($submitted) > 100) {
            throw new DomainConflict('invalid_cart', 'A cart must contain between 1 and 100 rows.');
        }
        $merged = [];
        foreach ($submitted as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);
            $price = Canonical::money((string) ($row['expected_unit_price'] ?? ''));
            if ($id < 1 || $quantity < 1 || $quantity > 1_000_000) {
                throw new DomainConflict('invalid_cart', 'Each cart row requires a product and valid quantity.');
            }
            if (isset($merged[$id])) {
                if ($merged[$id]['expected_unit_price'] !== $price) {
                    throw new DomainConflict('price_conflict', 'Duplicate product rows contain different expected prices.', ['product_id' => (string) $id]);
                }
                $quantity += $merged[$id]['quantity'];
            }
            if ($quantity > 1_000_000) {
                throw new DomainConflict('invalid_quantity', 'Combined product quantity exceeds the limit.', ['product_id' => (string) $id]);
            }
            $merged[$id] = ['product_id' => $id, 'quantity' => $quantity, 'expected_unit_price' => $price];
        }
        if (count($merged) > 100) {
            throw new DomainConflict('invalid_cart', 'A cart may contain at most 100 distinct products.');
        }
        ksort($merged, SORT_NUMERIC);

        return array_values($merged);
    }

    private function resolveReplay(Sale $sale, User $actor, string $fingerprint): Sale
    {
        if ($sale->created_by !== $actor->id || ! hash_equals($sale->request_fingerprint, $fingerprint)) {
            throw new DomainConflict('idempotency_conflict', 'This request key was already used with different data.');
        }

        return $this->loadSale($sale);
    }

    private function loadSale(Sale $sale): Sale
    {
        return $sale->load(['customer', 'creator', 'canceller', 'items']);
    }

    private function isRequestKeyCollision(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062 && str_contains($e->getMessage(), 'uq_sales_request');
    }
}
