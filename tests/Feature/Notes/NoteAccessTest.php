<?php

namespace Tests\Feature\Notes;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteAccessTest extends TestCase
{
    use RefreshDatabase;

    private function note(User $owner, bool $private, string $title = 'Test'): Note
    {
        return Note::create([
            'user_id' => $owner->id,
            'updated_by' => $owner->id,
            'title' => $title.' '.uniqid(),
            'body' => 'body',
            'is_private' => $private,
        ]);
    }

    public function test_user_cannot_update_another_users_public_note(): void
    {
        $author = User::factory()->create(['role' => 'user']);
        $other  = User::factory()->create(['role' => 'user']);
        $note   = $this->note($author, false);

        $this->actingAs($other)
            ->put(route('notes.update', $note), ['title' => 'Hacked', 'body' => 'x', 'is_private' => false])
            ->assertForbidden();

        $this->assertNotSame('Hacked', $note->fresh()->title);
    }

    public function test_user_cannot_delete_another_users_public_note(): void
    {
        $author = User::factory()->create(['role' => 'user']);
        $other  = User::factory()->create(['role' => 'user']);
        $note   = $this->note($author, false);

        $this->actingAs($other)
            ->delete(route('notes.destroy', $note))
            ->assertForbidden();

        $this->assertNotNull(Note::find($note->id));
    }

    public function test_moderator_can_update_any_public_note(): void
    {
        $author    = User::factory()->create(['role' => 'user']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $note      = $this->note($author, false);

        $this->actingAs($moderator)
            ->put(route('notes.update', $note), ['title' => 'Curated', 'body' => 'x', 'is_private' => false])
            ->assertRedirect();

        $this->assertSame('Curated', $note->fresh()->title);
    }

    public function test_moderator_cannot_touch_others_private_note(): void
    {
        $author    = User::factory()->create(['role' => 'user']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $note      = $this->note($author, true);

        $this->actingAs($moderator)
            ->get(route('notes.show', $note))
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_note(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer)
            ->post(route('notes.store'), ['title' => 'Nope', 'body' => 'x', 'is_private' => false])
            ->assertForbidden();
    }

    public function test_dashboard_scope_hides_private_notes_from_viewer(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $author = User::factory()->create(['role' => 'user']);

        $public  = $this->note($author, false, 'PublicDoc');
        $private = $this->note($author, true,  'PrivateDoc');

        $response = $this->actingAs($viewer)->get(route('dashboard'));
        $response->assertOk();

        $ids = collect($response->viewData('page')['props']['recent_notes'])->pluck('id')->all();
        $this->assertContains($public->id, $ids);
        $this->assertNotContains($private->id, $ids);
    }
}
