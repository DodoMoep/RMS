<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Beim weichen Loeschen wird die Artikelnummer freigegeben: Der Unique-Index und die
     * Validierungsregel zaehlen geloeschte Zeilen mit, die Nummer waere sonst fuer immer
     * gesperrt. Auftragspositionen behalten die Nummer als Schnappschuss (article_sku).
     */
    protected static function booted(): void
    {
        static::deleting(function (Article $article) {
            if (! $article->isForceDeleting() && $article->sku !== null) {
                $article->sku = null;
                $article->saveQuietly();
            }
        });
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
