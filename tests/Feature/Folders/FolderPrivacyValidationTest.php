<?php

namespace Tests\Feature\Folders;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderPrivacyValidationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_cannot_make_folder_private_while_it_contains_others_notes(): void
    {
        $alice = $this->user();
        $bob = $this->user();

        $folder = Folder::create([
            'user_id' => $alice->id,
            'name' => 'Shared',
            'is_private' => false,
        ]);

        Note::create([
            'user_id' => $bob->id,
            'updated_by' => $bob->id,
            'folder_id' => $folder->id,
            'title' => 'Bob note',
            'body' => '',
            'is_private' => false,
        ]);

        $response = $this->actingAs($alice)
            ->from('/dashboard')
            ->put(route('folders.update', $folder), [
                'name' => 'Shared',
                'is_private' => true,
            ]);

        $response->assertSessionHasErrors('is_private');
        $this->assertFalse($folder->fresh()->is_private);
    }

    public function test_can_make_folder_private_when_only_owner_notes_inside(): void
    {
        $alice = $this->user();

        $folder = Folder::create([
            'user_id' => $alice->id,
            'name' => 'Mine',
            'is_private' => false,
        ]);

        Note::create([
            'user_id' => $alice->id,
            'updated_by' => $alice->id,
            'folder_id' => $folder->id,
            'title' => 'Alice note',
            'body' => '',
            'is_private' => false,
        ]);

        $this->actingAs($alice)
            ->put(route('folders.update', $folder), [
                'name' => 'Mine',
                'is_private' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($folder->fresh()->is_private);
    }

    public function test_cannot_create_note_in_other_users_private_folder(): void
    {
        $alice = $this->user();
        $bob = $this->user();

        $alicePriv = Folder::create([
            'user_id' => $alice->id,
            'name' => 'AlicePrivate',
            'is_private' => true,
        ]);

        $response = $this->actingAs($bob)
            ->from('/dashboard')
            ->post(route('notes.store'), [
                'title' => 'Bob tries',
                'body' => '',
                'folder_id' => $alicePriv->id,
                'is_private' => false,
            ]);

        $response->assertSessionHasErrors('folder_id');
        $this->assertDatabaseMissing('notes', ['title' => 'Bob tries']);
    }

    public function test_cannot_move_note_into_other_users_private_folder(): void
    {
        $alice = $this->user();
        $bob = $this->user();

        $alicePriv = Folder::create([
            'user_id' => $alice->id,
            'name' => 'AlicePrivate',
            'is_private' => true,
        ]);

        $bobNote = Note::create([
            'user_id' => $bob->id,
            'updated_by' => $bob->id,
            'title' => 'Bob note',
            'body' => '',
            'is_private' => false,
        ]);

        $response = $this->actingAs($bob)
            ->from('/dashboard')
            ->put(route('notes.update', $bobNote), [
                'title' => 'Bob note',
                'body' => '',
                'folder_id' => $alicePriv->id,
                'is_private' => false,
            ]);

        $response->assertSessionHasErrors('folder_id');
        $this->assertNull($bobNote->fresh()->folder_id);
    }

    public function test_can_place_note_in_any_public_folder(): void
    {
        $alice = $this->user();
        $bob = $this->user();

        $alicePub = Folder::create([
            'user_id' => $alice->id,
            'name' => 'AlicePublic',
            'is_private' => false,
        ]);

        $this->actingAs($bob)
            ->post(route('notes.store'), [
                'title' => 'Bob in Alice folder',
                'body' => '',
                'folder_id' => $alicePub->id,
                'is_private' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notes', [
            'title' => 'Bob in Alice folder',
            'user_id' => $bob->id,
            'folder_id' => $alicePub->id,
        ]);
    }
}
