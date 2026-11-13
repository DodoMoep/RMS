<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProtocolItem extends Model
{
    use HasUuids;

    protected $fillable = ['protocol_id', 'inventory_item_id', 'label', 'state', 'comment', 'charge'];

    public function protocol()
    {
        return $this->belongsTo(Protocol::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
