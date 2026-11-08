<?php

namespace App\Models;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $seller_id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property array<int, string> $deliverables
 * @property PricingType $pricing_type
 * @property int $price_minor
 * @property string $currency
 * @property string|null $includes
 * @property int $delivery_days
 * @property int $revisions
 * @property string|null $exclusions
 * @property string|null $requirements
 * @property array<int, string>|null $images
 * @property string|null $supporting_info
 * @property bool $negotiation_enabled
 * @property ServiceStatus $status
 * @property Carbon|null $published_at
 */
#[Fillable([
    'seller_id',
    'category_id',
    'title',
    'slug',
    'description',
    'deliverables',
    'pricing_type',
    'price_minor',
    'currency',
    'includes',
    'delivery_days',
    'revisions',
    'exclusions',
    'requirements',
    'images',
    'supporting_info',
    'negotiation_enabled',
    'status',
    'published_at',
])]
class Service extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deliverables' => 'array',
            'images' => 'array',
            'pricing_type' => PricingType::class,
            'price_minor' => 'integer',
            'delivery_days' => 'integer',
            'revisions' => 'integer',
            'negotiation_enabled' => 'boolean',
            'status' => ServiceStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isPublished(): bool
    {
        return $this->status === ServiceStatus::Published;
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ServiceStatus::Published->value)
            ->whereNotNull('published_at');
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->whereLike('title', "%{$term}%")
                ->orWhereLike('description', "%{$term}%")
                ->orWhereLike('supporting_info', "%{$term}%");
        });
    }
}
