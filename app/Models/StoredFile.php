<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['original_name', 'disk', 'path', 'mime_type', 'size'])]
class StoredFile extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'uploaded_at';

    protected static function booted(): void
    {
        static::creating(function (self $file): void {
            $file->uploaded_at ??= now();
            $file->expires_at = $file->uploaded_at->copy()->addDay();
        });
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
