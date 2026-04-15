<?php

namespace Tests\Feature\Folders;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTreeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function folder(User $owner, bool $private, ?int $parent = null, string $name = 'F'): Folder
    {
        return Folder::create([
            'user_id' => $owner->id,
            'parent_id' => $parent,
            'name' => $name.' '.uniqid(),
            'is_private' => $private,
        ]);
    }

    private function note(User $owner, ?Folder $folder, bool $private): Note
    {
        return Note::create([
            'user_id' => $owner->id,
            'updated_by' => $owner->id,
            'folder_id' => $folder?->id,
            'title' => 'n '.uniqid(),
            'body' => '',
            'is_private' => $private,
        ]);
    }

    /**
     * Walk the tree and collect folder ids.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int>
     */
    private function ids(array $tree): array
    {
        $ids = [];
        foreach ($tree as $node) {
            $ids[] = $node['id'];
            $ids = array_merge($ids, $this->ids($node['children']));
        }

        return $ids;
    }

    public function test_viewer_only_sees_public_folders_with_visible_notes(): void
    {
        $viewer = $this->user('viewer');
        $author = $this->user('user');

        $publicWithNote = $this->folder($author, false);
        $publicEmpty    = $this->folder($author, false);
        $privateFolder  = $this->folder($author, true);

        $this->note($author, $publicWithNote, false);
        $this->note($author, $privateFolder, false); // public note in private folder — still hidden

        $ids = $this->ids(Folder::tree($viewer));

        $this->assertContains($publicWithNote->id, $ids);
        $this->assertNotContains($publicEmpty->id, $ids, 'empty non-owned public folder must be pruned');
        $this->assertNotContains($privateFolder->id, $ids, 'private folder must be hidden from viewer');
    }

    public function test_owner_sees_own_empty_folders(): void
    {
        $user = $this->user('user');

        $empty = $this->folder($user, false);

        $ids = $this->ids(Folder::tree($user));

        $this->assertContains($empty->id, $ids, 'owner always sees their own empty folders');
    }

    public function test_empty_non_owned_public_folder_is_pruned_for_other_users(): void
    {
        $alice = $this->user('user');
        $bob   = $this->user('user');

        $bobEmpty = $this->folder($bob, false);

        $ids = $this->ids(Folder::tree($alice));

        $this->assertNotContains($bobEmpty->id, $ids);
    }

    public function test_public_child_of_private_parent_is_hidden_for_non_owner(): void
    {
        $alice = $this->user('user');
        $bob   = $this->user('user');

        $alicePriv = $this->folder($alice, true);
        $alicePubChild = $this->folder($alice, false, $alicePriv->id);
        $this->note($alice, $alicePubChild, false); // public note to keep child "not empty"

        $ids = $this->ids(Folder::tree($bob));

        $this->assertNotContains($alicePriv->id, $ids);
        $this->assertNotContains($alicePubChild->id, $ids,
            'public child of private parent must be chain-pruned to preserve parent privacy');
    }

    public function test_owner_sees_their_private_folder_and_public_notes_inside(): void
    {
        $user = $this->user('user');

        $priv = $this->folder($user, true);
        $publicNote  = $this->note($user, $priv, false);
        $privateNote = $this->note($user, $priv, true);

        $tree = Folder::tree($user);
        $node = collect($tree)->firstWhere('id', $priv->id);

        $this->assertNotNull($node);
        $noteIds = collect($node['notes'])->pluck('id')->all();
        $this->assertContains($publicNote->id, $noteIds);
        $this->assertContains($privateNote->id, $noteIds);
    }

    public function test_moderator_does_not_see_others_private_folders(): void
    {
        $moderator = $this->user('moderator');
        $other     = $this->user('user');

        $othersPriv = $this->folder($other, true);
        $this->note($other, $othersPriv, false);

        $ids = $this->ids(Folder::tree($moderator));
        $this->assertNotContains($othersPriv->id, $ids,
            'moderator must not see other users private folders — impersonation is the explicit route');
    }

    public function test_tree_serializes_is_private_and_user_id(): void
    {
        $user   = $this->user('user');
        $folder = $this->folder($user, true);

        $tree = Folder::tree($user);
        $node = collect($tree)->firstWhere('id', $folder->id);

        $this->assertSame(true, $node['is_private']);
        $this->assertSame($user->id, $node['user_id']);
    }
}
