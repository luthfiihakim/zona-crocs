<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description',
        'base_price', 'compare_price', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Foto khusus warna lebih dulu, lalu foto umum (berlaku untuk semua warna). */
    public function imageUrls(?string $color = null): array
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();
        $wanted = $color === null ? null : mb_strtolower($color);

        $specific = $wanted === null
            ? collect()
            : $images->filter(fn ($i) => $i->color !== null && mb_strtolower($i->color) === $wanted);

        return $specific->concat($images->whereNull('color'))
            ->map(fn ($i) => $i->url)
            ->values()
            ->all();
    }

    public function coverUrl(): ?string
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return $images->first()?->url;
    }
}