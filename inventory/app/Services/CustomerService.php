<?php

namespace App\Services;

use App\Domain\DomainConflict;
use App\Models\Customer;
use App\Models\User;
use App\Support\Canonical;
use Illuminate\Support\Facades\DB;

final class CustomerService
{
    use GuardsDomain;

    public function create(User $actor, array $data): Customer
    {
        $this->requireActive($actor);

        return Customer::create($this->fields($data))->refresh();
    }

    public function update(User $actor, Customer $customer, array $data, int $expectedVersion): Customer
    {
        $this->requireActive($actor);

        return DB::transaction(function () use ($customer, $data, $expectedVersion): Customer {
            $locked = Customer::lockForUpdate()->findOrFail($customer->id);
            $this->assertVersion($locked->version, $expectedVersion);
            $locked->fill($this->fields($data));
            $locked->version++;
            $locked->save();

            return $locked;
        }, 3);
    }

    public function setArchived(User $actor, Customer $customer, bool $archived, int $expectedVersion): Customer
    {
        $this->requireManager($actor);

        return DB::transaction(function () use ($customer, $archived, $expectedVersion): Customer {
            $locked = Customer::lockForUpdate()->findOrFail($customer->id);
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

    private function fields(array $data): array
    {
        return [
            'full_name' => Canonical::text($data['full_name']),
            'email' => blank($data['email'] ?? null) ? null : Canonical::email($data['email']),
            'phone' => blank($data['phone'] ?? null) ? null : Canonical::text($data['phone']),
        ];
    }

    private function assertVersion(int $actual, int $expected): void
    {
        if ($actual !== $expected) {
            throw new DomainConflict('version_conflict', 'The customer changed. Reload and review.', ['current_version' => $actual]);
        }
    }
}
