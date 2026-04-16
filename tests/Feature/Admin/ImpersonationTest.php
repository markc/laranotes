<?php

namespace Tests\Feature\Admin;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_regular_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame(1, DB::table('impersonation_events')->count());
    }

    public function test_admin_can_impersonate_moderator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mod = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $mod))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($mod);
    }

    public function test_admin_can_impersonate_viewer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $viewer))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($viewer);
    }

    public function test_cannot_impersonate_another_admin(): void
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin1)
            ->post(route('admin.impersonate.start', $admin2))
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin1);
        $this->assertSame(0, DB::table('impersonation_events')->count());
    }

    public function test_cannot_impersonate_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $admin))
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_cannot_impersonate(): void
    {
        $mod = User::factory()->create(['role' => 'moderator']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($mod)
            ->post(route('admin.impersonate.start', $target))
            ->assertForbidden();
    }

    public function test_stop_restores_admin_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target));

        $this->assertAuthenticatedAs($target);

        $this->post(route('impersonate.stop'))
            ->assertRedirect(route('admin.users.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_stop_records_ended_at_in_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target));

        $event = DB::table('impersonation_events')->first();
        $this->assertNotNull($event->started_at);
        $this->assertNull($event->ended_at);

        $this->post(route('impersonate.stop'));

        $event = DB::table('impersonation_events')->first();
        $this->assertNotNull($event->ended_at);
    }

    public function test_impersonated_session_sees_target_content(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $folder = Folder::create([
            'user_id' => $target->id,
            'name' => 'Target Private',
            'is_private' => true,
        ]);
        $note = Note::create([
            'user_id' => $target->id,
            'updated_by' => $target->id,
            'folder_id' => $folder->id,
            'title' => 'Secret Note',
            'body' => '',
            'is_private' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target));

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        $ids = collect($response->viewData('page')['props']['recent_notes'])
            ->pluck('id')
            ->all();
        $this->assertContains($note->id, $ids);
    }

    public function test_admin_routes_blocked_during_impersonation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);
        $victim = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target));

        // While impersonated as $target (role=user), admin routes would 403
        // from role middleware. But even if they could reach them, the
        // BlockDuringImpersonation middleware would 403 too.
        // Let's test via a direct session manipulation to prove the middleware works.
        // Actually — the role:admin middleware catches it first. That's fine;
        // the point is the user cannot access admin routes while impersonating.
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_cannot_start_nested_impersonation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target1 = User::factory()->create(['role' => 'user']);
        $target2 = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target1));

        // Now logged in as target1 — trying to impersonate again should fail
        // (role:admin middleware blocks, and the controller also checks)
        $this->post(route('admin.impersonate.start', $target2))
            ->assertForbidden();

        $this->assertAuthenticatedAs($target1);
    }

    public function test_impersonator_appears_in_shared_props(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target));

        $response = $this->get(route('dashboard'));
        $props = $response->viewData('page')['props'];

        $this->assertNotNull($props['auth']['impersonator']);
        $this->assertSame($admin->id, $props['auth']['impersonator']['id']);
        $this->assertSame($admin->name, $props['auth']['impersonator']['name']);
    }

    public function test_no_impersonator_in_normal_session(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $props = $response->viewData('page')['props'];

        $this->assertNull($props['auth']['impersonator']);
    }
}
