<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_login_is_generic_case_normalized_and_rotates_into_a_bounded_session(): void
    {
        CarbonImmutable::setTestNow('2026-09-12 00:00:00 UTC');
        $user = User::factory()->create(['email' => 'person@example.test', 'password' => Hash::make('Correct Horse 1!')]);

        $this->postJson('/api/v1/login', ['email' => 'PERSON@example.test', 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonPath('error.code', 'invalid_credentials');
        $this->postJson('/api/v1/login', ['email' => 'PERSON@example.test', 'password' => 'Correct Horse 1!'])
            ->assertOk()->assertJsonPath('data.user.id', (string) $user->id)
            ->assertSessionHas('auth_started_at', now()->timestamp);

        CarbonImmutable::setTestNow('2026-09-12 00:31:00 UTC');
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error.code', 'session_expired');
    }

    public function test_absolute_lifetime_and_active_account_revocation_are_enforced(): void
    {
        $user = User::factory()->create();
        CarbonImmutable::setTestNow('2026-09-12 08:01:00 UTC');
        $this->actingAs($user)->withSession(['auth_started_at' => CarbonImmutable::parse('2026-09-12 00:00:00 UTC')->timestamp, 'auth_last_seen_at' => now()->timestamp])
            ->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error.code', 'session_expired');

        $revoked = User::factory()->create(['email' => 'revoked@example.test', 'password' => Hash::make('Correct Horse 2!')]);
        $this->postJson('/api/v1/login', ['email' => $revoked->email, 'password' => 'Correct Horse 2!'])->assertOk();
        $revoked->update(['is_active' => false]);
        Auth::forgetGuards();
        $this->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('error.code', 'account_inactive');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'nobody@example.test', 'password' => 'incorrect'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/login', ['email' => 'nobody@example.test', 'password' => 'incorrect'])
            ->assertTooManyRequests()->assertJsonPath('error.code', 'rate_limited');
    }
}
