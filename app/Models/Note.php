<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'user_id',
        'updated_by',
        'title',
        'slug',
        'body',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Note $note) {
            if (empty($note->slug)) {
                $note->slug = static::generateUniqueSlug($note->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'note';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('is_private', false)->orWhere('user_id', $user->id);
        });
    }
}
