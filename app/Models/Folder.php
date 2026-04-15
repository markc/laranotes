<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'user_id', 'name', 'slug', 'sort_order', 'is_private'];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Folder $folder) {
            if (empty($folder->slug)) {
                $folder->slug = Str::slug($folder->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build a nested folder tree visible to the given user.
     *
     * A folder is "directly visible" if it is public or owned by the viewer.
     * A folder is "chain-visible" if it is directly visible AND every ancestor
     * in its parent chain is directly visible — this prevents public children
     * of private folders from leaking out at the root.
     *
     * After building the tree, any non-owned public folder with no visible
     * notes and no surviving children is pruned (graffiti prevention). The
     * viewer's own empty folders are always kept so they have somewhere to
     * work.
     */
    public static function tree(User $user): array
    {
        $all = static::with(['notes' => function ($query) use ($user) {
            $query->visibleTo($user)->orderBy('title');
        }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $byId = $all->keyBy('id');

        $directlyVisible = $all->filter(
            fn (Folder $f) => static::isDirectlyVisible($f, $user)
        );

        $visibleIds = $directlyVisible->pluck('id')->flip();

        $chainVisible = $directlyVisible->filter(function (Folder $f) use ($visibleIds, $byId) {
            $parentId = $f->parent_id;
            while ($parentId !== null) {
                if (! $visibleIds->has($parentId)) {
                    return false;
                }
                $parentId = $byId->get($parentId)?->parent_id;
            }

            return true;
        });

        return static::buildBranches($chainVisible, null, $user);
    }

    private static function isDirectlyVisible(Folder $folder, User $user): bool
    {
        if ($user->role === Role::Viewer) {
            return ! $folder->is_private;
        }

        return ! $folder->is_private || $folder->user_id === $user->id;
    }

    private static function buildBranches(Collection $all, ?int $parentId, User $user): array
    {
        return $all->where('parent_id', $parentId)->map(function (Folder $folder) use ($all, $user) {
            $children = static::buildBranches($all, $folder->id, $user);
            $notes = $folder->notes->map(fn (Note $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'slug' => $n->slug,
                'is_private' => (bool) $n->is_private,
                'user_id' => $n->user_id,
                'updated_at' => $n->updated_at?->toIso8601String(),
            ])->values()->all();

            $isOwner = $folder->user_id === $user->id;
            if (! $isOwner && empty($notes) && empty($children)) {
                return null;
            }

            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'slug' => $folder->slug,
                'parent_id' => $folder->parent_id,
                'is_private' => (bool) $folder->is_private,
                'user_id' => $folder->user_id,
                'notes' => $notes,
                'children' => $children,
            ];
        })->filter()->values()->all();
    }
}
