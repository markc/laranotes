<?php

namespace Tests\Feature\Invites;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvite(User $inviter, string $role, array $overrides = []): Invite
    {
        return Invite::create(array_merge([
            'email' => 'target-'.uniqid().'@example.com',
            'role' => $role,
            'token' => Str::random(64),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(14),
        ], $overrides));
    }

    public function test_registration_route_is_gone(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_viewer_cannot_reach_invites_index(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewer)->get(route('invites.index'))->assertForbidden();
    }

    public function test_user_cannot_reach_invites_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get(route('invites.index'))->assertForbidden();
    }

    public function test_moderator_can_create_user_invite(): void
    {
        $mod = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($mod)
            ->post(route('invites.store'), [
                'email' => 'alice@example.com',
                'role' => 'user',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invites', [
            'email' => 'alice@example.com',
            'role' => 'user',
            'invited_by' => $mod->id,
        ]);
    }

    public function test_moderator_cannot_invite_admin_or_moderator(): void
    {
        $mod = User::factory()->create(['role' => 'moderator']);

        foreach (['admin', 'moderator'] as $target) {
            $response = $this->actingAs($mod)
                ->from(route('invites.index'))
                ->post(route('invites.store'), [
                    'email' => "target-$target@example.com",
                    'role' => $target,
                ]);
            $response->assertSessionHasErrors('role');
        }

        $this->assertDatabaseCount('invites', 0);
    }

    public function test_admin_can_invite_any_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['admin', 'moderator', 'user', 'viewer'] as $target) {
            $this->actingAs($admin)
                ->post(route('invites.store'), [
                    'email' => "target-$target@example.com",
                    'role' => $target,
                ])
                ->assertRedirect();
        }

        $this->assertDatabaseCount('invites', 4);
    }

    public function test_cannot_invite_existing_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)
            ->from(route('invites.index'))
            ->post(route('invites.store'), [
                'email' => $existing->email,
                'role' => 'user',
            ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_valid_invite_token_renders_accept_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invite = $this->makeInvite($admin, 'user');

        $this->get("/invite/{$invite->token}")->assertOk();
    }

    public function test_expired_invite_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invite = $this->makeInvite($admin, 'user', [
            'expires_at' => now()->subDay(),
        ]);

        $this->get("/invite/{$invite->token}")->assertNotFound();
    }

    public function test_revoked_invite_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invite = $this->makeInvite($admin, 'user', [
            'revoked_at' => now(),
        ]);

        $this->get("/invite/{$invite->token}")->assertNotFound();
    }

    public function test_accepted_invite_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invite = $this->makeInvite($admin, 'user', [
            'accepted_at' => now(),
        ]);

        $this->get("/invite/{$invite->token}")->assertNotFound();
    }

    public function test_bogus_token_returns_404(): void
    {
        $this->get('/invite/'.Str::random(64))->assertNotFound();
    }

    public function test_accept_creates_user_with_preassigned_role_and_logs_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invite = $this->makeInvite($admin, 'moderator', [
            'email' => 'newmod@example.com',
        ]);

        $response = $this->post("/invite/{$invite->token}", [
            'name' => 'New Mod',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'newmod@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('moderator', $user->role->value);
        $this->assertTrue(Hash::check('correct-horse-battery-staple', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($invite->fresh()->accepted_at);
    }

    public function test_accept_rejects_weak_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invite = $this->makeInvite($admin, 'user');

        $this->from("/invite/{$invite->token}")
            ->post("/invite/{$invite->token}", [
                'name' => 'Weak',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => $invite->email]);
    }

    public function test_moderator_can_revoke_own_invite(): void
    {
        $mod = User::factory()->create(['role' => 'moderator']);
        $invite = $this->makeInvite($mod, 'user');

        $this->actingAs($mod)
            ->delete(route('invites.destroy', $invite))
            ->assertRedirect();

        $this->assertNotNull($invite->fresh()->revoked_at);
    }

    public function test_moderator_cannot_revoke_anothers_invite(): void
    {
        $mod1 = User::factory()->create(['role' => 'moderator']);
        $mod2 = User::factory()->create(['role' => 'moderator']);
        $invite = $this->makeInvite($mod1, 'user');

        $this->actingAs($mod2)
            ->delete(route('invites.destroy', $invite))
            ->assertForbidden();

        $this->assertNull($invite->fresh()->revoked_at);
    }

    public function test_admin_can_revoke_any_invite(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mod = User::factory()->create(['role' => 'moderator']);
        $invite = $this->makeInvite($mod, 'user');

        $this->actingAs($admin)
            ->delete(route('invites.destroy', $invite))
            ->assertRedirect();

        $this->assertNotNull($invite->fresh()->revoked_at);
    }
}
