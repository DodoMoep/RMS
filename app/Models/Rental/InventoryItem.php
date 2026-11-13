<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'sku', 'description'];

    public function halls()
    {
        return $this->belongsToMany(Hall::class, 'hall_inventory')
            ->withPivot('quantity')->withTimestamps();
    }
}
