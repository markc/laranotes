<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_users_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_access_users_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_can_promote_user_to_moderator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $target), ['role' => 'moderator'])
            ->assertRedirect();

        $this->assertSame('moderator', $target->fresh()->role->value);
    }

    public function test_cannot_demote_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.update', $admin), ['role' => 'user']);

        $response->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_can_demote_admin_when_another_admin_exists(): void
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin1)
            ->patch(route('admin.users.update', $admin2), ['role' => 'user'])
            ->assertRedirect();

        $this->assertSame('user', $admin2->fresh()->role->value);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']); // another admin exists

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.update', $admin), ['role' => 'moderator']);

        $response->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHasErrors('user');
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_admin_can_delete_non_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect();

        $this->assertNull(User::find($target->id));
    }

    public function test_role_change_rejects_invalid_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.update', $target), ['role' => 'superadmin'])
            ->assertSessionHasErrors('role');

        $this->assertSame('user', $target->fresh()->role->value);
    }

    public function test_can_hints_shape_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $props = $response->viewData('page')['props'];
        $this->assertSame('admin', $props['auth']['role']);
        $this->assertTrue($props['auth']['canHints']['createNotes']);
        $this->assertTrue($props['auth']['canHints']['createFolders']);
        $this->assertTrue($props['auth']['canHints']['moderate']);
        $this->assertTrue($props['auth']['canHints']['manageUsers']);
    }

    public function test_can_hints_shape_for_viewer(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $props = $response->viewData('page')['props'];
        $this->assertSame('viewer', $props['auth']['role']);
        $this->assertFalse($props['auth']['canHints']['createNotes']);
        $this->assertFalse($props['auth']['canHints']['createFolders']);
        $this->assertFalse($props['auth']['canHints']['moderate']);
        $this->assertFalse($props['auth']['canHints']['manageUsers']);
    }
}
