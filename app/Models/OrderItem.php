<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'article_id',
        'article_name',
        'article_sku',
        'article_price',
        'quantity_ordered',
        'quantity_packed',
        'is_packed',
        'packed_at',
        'packed_by',
        'notes',
    ];

    protected $casts = [
        'is_packed' => 'boolean',
        'packed_at' => 'datetime',
        'quantity_ordered' => 'integer',
        'quantity_packed' => 'integer',
        'article_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function packedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function canBeModified(): bool
    {
        return !$this->is_packed && $this->order->canBeModified();
    }

    public function markAsPacked(int $quantity = null): void
    {
        $packQuantity = $quantity ?? $this->quantity_ordered;

        if ($packQuantity > $this->remainingQuantity()) {
            throw new \Exception('Cannot pack more than remaining quantity.');
        }

        $newQuantityPacked = $this->quantity_packed + $packQuantity;
        $fullyPacked = $newQuantityPacked >= $this->quantity_ordered;

        $this->update([
            'quantity_packed' => $newQuantityPacked,
            'is_packed' => $fullyPacked,
            'packed_at' => $fullyPacked ? now() : $this->packed_at,
            'packed_by' => auth()->id(),
        ]);

        $this->order->logHistory('item_packed', "Item '{$this->article_name}' packed ({$packQuantity} units)", [
            'item_id' => $this->id,
            'item_name' => $this->article_name,
            'quantity_packed' => $packQuantity,
            'packed_by' => auth()->user()->name,
        ]);
    }

    public function unpack(): void
    {
        if (!$this->order->canBeModified()) {
            throw new \Exception('Cannot unpack items from a locked order.');
        }

        $this->update([
            'quantity_packed' => 0,
            'is_packed' => false,
            'packed_at' => null,
            'packed_by' => null,
        ]);

        $this->order->logHistory('item_unpacked', "Item '{$this->article_name}' unpacked", [
            'item_id' => $this->id,
            'item_name' => $this->article_name,
        ]);
    }

    public function remainingQuantity(): int
    {
        return $this->quantity_ordered - $this->quantity_packed;
    }
}
