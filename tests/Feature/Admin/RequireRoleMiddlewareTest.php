<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\RequireRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequireRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register ad-hoc routes that exercise the middleware directly,
        // independent of the real admin routes. This isolates middleware
        // behaviour from downstream policies or controllers.
        Route::middleware(['web', 'auth', RequireRole::class.':admin'])
            ->get('/_test/admin-only', fn () => 'ok');

        Route::middleware(['web', 'auth', RequireRole::class.':admin,moderator'])
            ->get('/_test/curator', fn () => 'ok');
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->get('/_test/admin-only')->assertRedirect(route('login'));
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/_test/admin-only')->assertForbidden();
    }

    public function test_correct_role_passes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/_test/admin-only')->assertOk();
    }

    public function test_viewer_blocked_from_curator_routes(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewer)->get('/_test/curator')->assertForbidden();
    }

    public function test_allowlist_accepts_any_listed_role(): void
    {
        $mod = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($mod)->get('/_test/curator')->assertOk();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/_test/curator')->assertOk();
    }
}
