<?php

namespace App\Services;

use App\Domain\DomainConflict;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Canonical;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StockService
{
    use GuardsDomain;

    public function change(User $actor, Product $product, string $operation, int $quantity, string $note, string $requestKey, ?int $expectedVersion = null): StockMovement
    {
        $this->requireManager($actor);
        $requestKey = strtolower(trim($requestKey));
        if (! Str::isUuid($requestKey) || ! in_array($operation, ['restock', 'adjustment'], true)) {
            throw new DomainConflict('invalid_request', 'Invalid stock operation or request key.');
        }
        if ($quantity === 0 || abs($quantity) > 1_000_000 || ($operation === 'restock' && $quantity < 1)) {
            throw new DomainConflict('invalid_quantity', 'Quantity is outside the allowed range.');
        }
        if ($operation === 'adjustment' && $expectedVersion === null) {
            throw new DomainConflict('version_required', 'A correction requires the displayed product version.');
        }

        $note = Canonical::text($note);
        $canonical = [
            'actor_id' => (string) $actor->id,
            'product_id' => (string) $product->id,
            'operation' => $operation,
            'quantity_delta' => $quantity,
            'note' => $note,
            'expected_version' => $operation === 'adjustment' ? (string) $expectedVersion : null,
        ];
        $fingerprint = Canonical::fingerprint($canonical);

        if ($existing = StockMovement::where('request_key', $requestKey)->first()) {
            return $this->resolveReplay($existing, $actor, $fingerprint);
        }

        try {
            return DB::transaction(function () use ($actor, $product, $operation, $quantity, $note, $requestKey, $fingerprint, $expectedVersion): StockMovement {
                if ($existing = StockMovement::where('request_key', $requestKey)->lockForUpdate()->first()) {
                    return $this->resolveReplay($existing, $actor, $fingerprint);
                }

                $locked = Product::lockForUpdate()->findOrFail($product->id);
                if ($operation === 'restock' && $locked->archived_at) {
                    throw new DomainConflict('product_inactive', 'Archived products cannot be restocked.');
                }
                if ($operation === 'adjustment' && $locked->version !== $expectedVersion) {
                    throw new DomainConflict('version_conflict', 'The product changed. Reload before correcting stock.', ['current_version' => $locked->version]);
                }

                $newStock = $locked->stock_on_hand + $quantity;
                if ($newStock < 0 || $newStock > 1_000_000_000) {
                    throw new DomainConflict('stock_bounds', 'The resulting stock is outside the allowed range.', ['stock_on_hand' => $locked->stock_on_hand]);
                }

                $locked->stock_on_hand = $newStock;
                $locked->version++;
                $locked->save();

                return StockMovement::create([
                    'product_id' => $locked->id,
                    'sale_item_id' => null,
                    'quantity_delta' => $quantity,
                    'resulting_stock' => $newStock,
                    'reason' => $operation,
                    'created_by' => $actor->id,
                    'request_key' => $requestKey,
                    'request_fingerprint' => $fingerprint,
                    'note' => $note,
                    'created_at' => now(),
                ]);
            }, 3);
        } catch (QueryException $e) {
            if ($this->isRequestKeyCollision($e) && ($existing = StockMovement::where('request_key', $requestKey)->first())) {
                return $this->resolveReplay($existing, $actor, $fingerprint);
            }
            throw $e;
        }
    }

    private function resolveReplay(StockMovement $movement, User $actor, string $fingerprint): StockMovement
    {
        if ($movement->created_by !== $actor->id || ! hash_equals($movement->request_fingerprint ?? '', $fingerprint)) {
            throw new DomainConflict('idempotency_conflict', 'This request key was already used with different data.');
        }

        return $movement;
    }

    private function isRequestKeyCollision(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062 && str_contains($e->getMessage(), 'uq_movements_request');
    }
}
