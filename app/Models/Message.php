<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['transaction_id', 'user_id', 'body'])]
class Message extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return array{
     *     id: int,
     *     body: string,
     *     user_id: int,
     *     user_name: string,
     *     created_at: string|null,
     *     attachments: list<array{id: int, name: string, mime: string, size: int, url: string}>
     * }
     */
    public function toPayload(): array
    {
        $attachments = [];

        foreach ($this->attachments as $attachment) {
            $attachments[] = [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'mime' => $attachment->mime,
                'size' => $attachment->size,
                'url' => $attachment->url(),
            ];
        }

        return [
            'id' => $this->id,
            'body' => $this->body,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'attachments' => $attachments,
        ];
    }
}
