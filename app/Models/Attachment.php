<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $attachable_type
 * @property int|null $attachable_id
 * @property string $collection
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'attachable_type',
    'attachable_id',
    'collection',
    'disk',
    'path',
    'original_name',
    'mime',
    'size',
])]
class Attachment extends Model
{
    /**
     * Collections that are safe to serve without authorization.
     *
     * @var list<string>
     */
    public const PUBLIC_COLLECTIONS = ['service_image', 'avatar'];

    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPublic(): bool
    {
        return in_array($this->collection, self::PUBLIC_COLLECTIONS, true);
    }

    public function url(): string
    {
        if ($this->isPublic()) {
            // Host-relative so the URL is correct whatever domain or port the
            // app is served from (APP_URL is often stale in local dev).
            return '/storage/'.ltrim($this->path, '/');
        }

        return route('attachments.show', $this);
    }
}
