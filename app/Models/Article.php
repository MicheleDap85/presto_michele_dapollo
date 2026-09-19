<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

#[Fillable(['title', 'description', 'price', 'category_id', 'user_id', 'is_accepted'])]
class Article extends Model
{
    use HasFactory, Searchable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Keep the three-state review flag: pending (null), accepted, or rejected.
     */
    protected function isAccepted(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?bool {
                if ($value === null) {
                    return null;
                }

                return (bool) $value;
            },
        );
    }

    public function setAccepted(?bool $value): bool
    {
        $this->is_accepted = $value;
        $this->save();

        return true;
    }

    public static function toBeRevisedCount(): int
    {
        return self::query()->toBeRevised()->count();
    }

    #[Scope]
    protected function accepted(Builder $query): Builder
    {
        return $query->where('is_accepted', true);
    }

    #[Scope]
    protected function toBeRevised(Builder $query): Builder
    {
        return $query->whereNull('is_accepted')->oldest();
    }

    /**
     * @return array{id: mixed, title: mixed, description: mixed, category: string|null}
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category?->name,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}
