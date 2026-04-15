<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'user_id', 'name', 'slug', 'sort_order'];

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
     * Private notes are filtered to only show the author's own.
     */
    public static function tree(User $user): array
    {
        $folders = static::with(['notes' => function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('is_private', false)->orWhere('user_id', $user->id);
            })->orderBy('title');
        }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return static::buildBranches($folders, null);
    }

    private static function buildBranches(Collection $all, ?int $parentId): array
    {
        return $all->where('parent_id', $parentId)->map(function (Folder $folder) use ($all) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'slug' => $folder->slug,
                'parent_id' => $folder->parent_id,
                'notes' => $folder->notes->map(fn (Note $n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'slug' => $n->slug,
                    'is_private' => (bool) $n->is_private,
                    'user_id' => $n->user_id,
                    'updated_at' => $n->updated_at?->toIso8601String(),
                ])->values()->all(),
                'children' => static::buildBranches($all, $folder->id),
            ];
        })->values()->all();
    }
}
