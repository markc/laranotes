<?php

namespace Tests\Feature\Notes;

use App\Enums\Role;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotePolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeNote(User $owner, bool $private): Note
    {
        return Note::create([
            'user_id' => $owner->id,
            'updated_by' => $owner->id,
            'title' => 'T '.uniqid(),
            'body' => 'b',
            'is_private' => $private,
        ]);
    }

    public static function matrix(): array
    {
        // [role, shape, view, update, delete]
        // shape: own-pub, own-priv, other-pub, other-priv
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
    public function test_policy_matrix(string $role, string $shape, bool $view, bool $update, bool $delete): void
    {
        $actor = $this->makeUser($role);
        $other = $this->makeUser('user');

        [$ownerUser, $private] = match ($shape) {
            'own-pub' => [$actor, false],
            'own-priv' => [$actor, true],
            'other-pub' => [$other, false],
            'other-priv' => [$other, true],
        };

        $note = $this->makeNote($ownerUser, $private);

        $this->assertSame($view, $actor->can('view', $note), "view: $role/$shape");
        $this->assertSame($update, $actor->can('update', $note), "update: $role/$shape");
        $this->assertSame($delete, $actor->can('delete', $note), "delete: $role/$shape");
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
    public function test_create_by_role(string $role, bool $expected): void
    {
        $user = $this->makeUser($role);
        $this->assertSame($expected, $user->can('create', Note::class));
    }

    public function test_scope_viewer_excludes_all_private_notes(): void
    {
        $viewer = $this->makeUser('viewer');
        $author = $this->makeUser('user');

        $public = $this->makeNote($author, false);
        $private = $this->makeNote($author, true);

        $visible = Note::visibleTo($viewer)->pluck('id')->all();
        $this->assertContains($public->id, $visible);
        $this->assertNotContains($private->id, $visible);
    }

    public function test_scope_user_sees_own_private_plus_public(): void
    {
        $user = $this->makeUser('user');
        $other = $this->makeUser('user');

        $ownPriv = $this->makeNote($user, true);
        $otherPub = $this->makeNote($other, false);
        $otherPriv = $this->makeNote($other, true);

        $visible = Note::visibleTo($user)->pluck('id')->all();
        $this->assertContains($ownPriv->id, $visible);
        $this->assertContains($otherPub->id, $visible);
        $this->assertNotContains($otherPriv->id, $visible);
    }
}
