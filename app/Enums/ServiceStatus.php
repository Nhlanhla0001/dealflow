<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Unpublished => 'Unpublished',
            self::Archived => 'Archived',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
