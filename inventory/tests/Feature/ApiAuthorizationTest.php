<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_has_a_stable_error_shape(): void
    {
        $this->getJson('/api/v1/products')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated')
            ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
    }

    public function test_clerk_cannot_manage_catalog_but_can_create_a_customer(): void
    {
        $clerk = User::factory()->create();
        $this->actingAs($clerk)->postJson('/api/v1/categories', ['name' => 'blocked'])
            ->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->actingAs($clerk)->postJson('/api/v1/customers', ['full_name' => 'Ada Lovelace', 'email' => 'ADA@EXAMPLE.TEST'])
            ->assertCreated()->assertJsonPath('data.email', 'ada@example.test');
    }

    public function test_api_serializes_ids_and_money_as_strings_and_reports_replays(): void
    {
        $manager = User::factory()->manager()->create();
        $category = $this->actingAs($manager)->postJson('/api/v1/categories', ['name' => 'Hardware'])
            ->assertCreated()->json('data');
        $product = $this->actingAs($manager)->postJson('/api/v1/products', [
            'category_id' => $category['id'], 'sku' => 'H-1', 'name' => 'Hammer', 'unit_price' => '15.5', 'reorder_level' => 2,
        ])->assertCreated()->json('data');
        $this->assertIsString($product['id']);
        $this->assertSame('15.50', $product['unit_price']);

        $key = (string) Str::uuid();
        $payload = ['quantity' => 5, 'note' => 'Opening stock', 'request_key' => $key];
        $this->actingAs($manager)->postJson("/api/v1/products/{$product['id']}/restocks", $payload)->assertCreated();
        $this->actingAs($manager)->postJson("/api/v1/products/{$product['id']}/restocks", $payload)
            ->assertOk()->assertJsonPath('replayed', true);
    }

    public function test_validation_and_domain_conflicts_are_machine_readable(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->postJson('/api/v1/products', [])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['category_id', 'sku', 'name', 'unit_price', 'reorder_level']]]);

        $category = $this->actingAs($manager)->postJson('/api/v1/categories', ['name' => 'one'])->json('data');
        $product = $this->actingAs($manager)->postJson('/api/v1/products', ['category_id' => $category['id'], 'sku' => 'X-1', 'name' => 'X', 'unit_price' => '1.00', 'reorder_level' => 0])->json('data');
        $this->actingAs($manager)->postJson("/api/v1/products/{$product['id']}/restocks", ['quantity' => 1, 'note' => 'one', 'request_key' => (string) Str::uuid()]);
        $this->actingAs($manager)->postJson("/api/v1/products/{$product['id']}/archive", ['expected_version' => 2])
            ->assertConflict()->assertJsonPath('error.code', 'stock_not_zero');
    }
}
