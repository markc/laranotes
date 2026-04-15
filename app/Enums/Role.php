<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';
    case Viewer = 'viewer';

    public function canCreate(): bool
    {
        return $this !== self::Viewer;
    }

    public function canModerate(): bool
    {
        return $this === self::Admin || $this === self::Moderator;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Moderator => 'Moderator',
            self::User => 'User',
            self::Viewer => 'Viewer',
        };
    }
}
