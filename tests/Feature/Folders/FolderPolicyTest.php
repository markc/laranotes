<?php

namespace Tests\Feature\Folders;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FolderPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeFolder(User $owner, bool $private): Folder
    {
        return Folder::create([
            'user_id' => $owner->id,
            'name' => 'F '.uniqid(),
            'is_private' => $private,
        ]);
    }

    public static function matrix(): array
    {
        return [
            'admin own-pub' => ['admin',     'own-pub',   true,  true,  true],
            'admin own-priv' => ['admin',     'own-priv',  true,  true,  true],
            'admin other-pub' => ['admin',     'other-pub', true,  true,  true],
            'admin other-priv' => ['admin',     'other-priv', true, false, false],

            'moderator own-pub' => ['moderator', 'own-pub',   true,  true,  true],
            'moderator own-priv' => ['moderator', 'own-priv',  true,  true,  true],
            'moderator other-pub' => ['moderator', 'other-pub', true,  true,  true],
            'moderator other-priv' => ['moderator', 'other-priv', false, false, false],

            'user own-pub' => ['user',      'own-pub',   true,  true,  true],
            'user own-priv' => ['user',      'own-priv',  true,  true,  true],
            'user other-pub' => ['user',      'other-pub', true,  false, false],
            'user other-priv' => ['user',      'other-priv', false, false, false],

            'viewer own-pub' => ['viewer',    'own-pub',   true,  false, false],
            'viewer own-priv' => ['viewer',    'own-priv',  true,  false, false],
            'viewer other-pub' => ['viewer',    'other-pub', true,  false, false],
            'viewer other-priv' => ['viewer',    'other-priv', false, false, false],
        ];
    }

    #[DataProvider('matrix')]
    public function test_folder_policy_matrix(string $role, string $shape, bool $view, bool $update, bool $delete): void
    {
        $actor = $this->makeUser($role);
        $other = $this->makeUser('user');

        [$owner, $private] = match ($shape) {
            'own-pub' => [$actor, false],
            'own-priv' => [$actor, true],
            'other-pub' => [$other, false],
            'other-priv' => [$other, true],
        };

        $folder = $this->makeFolder($owner, $private);

        $this->assertSame($view, $actor->can('view', $folder), "view: $role/$shape");
        $this->assertSame($update, $actor->can('update', $folder), "update: $role/$shape");
        $this->assertSame($delete, $actor->can('delete', $folder), "delete: $role/$shape");
    }

    public static function createMatrix(): array
    {
        return [
            'admin' => ['admin',     true],
            'moderator' => ['moderator', true],
            'user' => ['user',      true],
            'viewer' => ['viewer',    false],
        ];
    }

    #[DataProvider('createMatrix')]
    public function test_folder_create_by_role(string $role, bool $expected): void
    {
        $user = $this->makeUser($role);
        $this->assertSame($expected, $user->can('create', Folder::class));
    }

    public function test_viewer_cannot_create_folder_via_http(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer)
            ->post(route('folders.store'), ['name' => 'Attempt'])
            ->assertForbidden();
    }
}
