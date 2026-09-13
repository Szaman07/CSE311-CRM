<?php

namespace App\Services;

use App\Domain\DomainConflict;
use App\Models\Category;
use App\Models\User;
use App\Support\Canonical;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    use GuardsDomain;

    public function create(User $actor, string $name): Category
    {
        $this->requireManager($actor);
        try {
            return Category::create(['name' => Canonical::category($name)])->refresh();
        } catch (QueryException $e) {
            $this->throwDuplicate($e);
        }
    }

    public function update(User $actor, Category $category, string $name, int $expectedVersion): Category
    {
        $this->requireManager($actor);
        try {
            return DB::transaction(function () use ($category, $name, $expectedVersion): Category {
                $locked = Category::lockForUpdate()->findOrFail($category->id);
                $this->assertVersion($locked->version, $expectedVersion);
                $locked->name = Canonical::category($name);
                $locked->version++;
                $locked->save();

                return $locked;
            }, 3);
        } catch (QueryException $e) {
            $this->throwDuplicate($e);
        }
    }

    public function setArchived(User $actor, Category $category, bool $archived, int $expectedVersion): Category
    {
        $this->requireManager($actor);

        return DB::transaction(function () use ($category, $archived, $expectedVersion): Category {
            $locked = Category::lockForUpdate()->findOrFail($category->id);
            if (($locked->archived_at !== null) === $archived) {
                return $locked;
            }
            $this->assertVersion($locked->version, $expectedVersion);
            $locked->archived_at = $archived ? now() : null;
            $locked->version++;
            $locked->save();

            return $locked;
        }, 3);
    }

    private function assertVersion(int $actual, int $expected): void
    {
        if ($actual !== $expected) {
            throw new DomainConflict('version_conflict', 'The category changed. Reload and review.', ['current_version' => $actual]);
        }
    }

    private function throwDuplicate(QueryException $e): never
    {
        if (($e->errorInfo[1] ?? null) === 1062 && str_contains($e->getMessage(), 'uq_categories_name')) {
            throw new DomainConflict('duplicate_category', 'A category with that canonical name already exists.');
        }
        throw $e;
    }
}
