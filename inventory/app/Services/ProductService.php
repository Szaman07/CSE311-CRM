<?php

namespace App\Services;

use App\Domain\DomainConflict;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Canonical;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    use GuardsDomain;

    public function create(User $actor, array $data): Product
    {
        $this->requireManager($actor);
        try {
            return DB::transaction(function () use ($data): Product {
                $category = Category::lockForUpdate()->findOrFail($data['category_id']);
                if ($category->archived_at) {
                    throw new DomainConflict('category_inactive', 'Choose an active category.');
                }

                return Product::create([
                    'category_id' => $category->id,
                    'sku' => Canonical::sku($data['sku']),
                    'name' => Canonical::text($data['name']),
                    'unit_price' => Canonical::money((string) $data['unit_price']),
                    'reorder_level' => (int) $data['reorder_level'],
                ])->refresh();
            }, 3);
        } catch (QueryException $e) {
            $this->throwDuplicate($e);
        }
    }

    public function update(User $actor, Product $product, array $data, int $expectedVersion): Product
    {
        $this->requireManager($actor);
        try {
            return DB::transaction(function () use ($product, $data, $expectedVersion): Product {
                $category = Category::lockForUpdate()->findOrFail($data['category_id']);
                if ($category->archived_at) {
                    throw new DomainConflict('category_inactive', 'Choose an active category.');
                }
                $locked = Product::lockForUpdate()->findOrFail($product->id);
                $this->assertVersion($locked->version, $expectedVersion);
                $locked->fill([
                    'category_id' => $category->id,
                    'sku' => Canonical::sku($data['sku']),
                    'name' => Canonical::text($data['name']),
                    'unit_price' => Canonical::money((string) $data['unit_price']),
                    'reorder_level' => (int) $data['reorder_level'],
                ]);
                $locked->version++;
                $locked->save();

                return $locked;
            }, 3);
        } catch (QueryException $e) {
            $this->throwDuplicate($e);
        }
    }

    public function setArchived(User $actor, Product $product, bool $archived, int $expectedVersion): Product
    {
        $this->requireManager($actor);

        return DB::transaction(function () use ($product, $archived, $expectedVersion): Product {
            $locked = Product::lockForUpdate()->findOrFail($product->id);
            if (($locked->archived_at !== null) === $archived) {
                return $locked;
            }
            $this->assertVersion($locked->version, $expectedVersion);
            if ($archived && $locked->stock_on_hand !== 0) {
                throw new DomainConflict('stock_not_zero', 'Reduce stock to zero before archiving.', ['stock_on_hand' => $locked->stock_on_hand]);
            }
            $locked->archived_at = $archived ? now() : null;
            $locked->version++;
            $locked->save();

            return $locked;
        }, 3);
    }

    private function assertVersion(int $actual, int $expected): void
    {
        if ($actual !== $expected) {
            throw new DomainConflict('version_conflict', 'The product changed. Reload and review.', ['current_version' => $actual]);
        }
    }

    private function throwDuplicate(QueryException $e): never
    {
        if (($e->errorInfo[1] ?? null) === 1062 && str_contains($e->getMessage(), 'uq_products_sku')) {
            throw new DomainConflict('duplicate_sku', 'A product with that canonical SKU already exists.');
        }
        throw $e;
    }
}
