<?php
namespace App\Models\Rental;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Hall extends Model {

    use HasUuids;

    protected $fillable = [
        'name','address','notes'
    ];

    public function rentals() {
        return $this->hasMany(Rental::class);
    }

    public function inventory(){
        return $this->belongsToMany(InventoryItem::class,'hall_inventory')
            ->withPivot('quantity')->withTimestamps();
    }
}
