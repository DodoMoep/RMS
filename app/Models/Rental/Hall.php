<?php
namespace App\Models\Rental;


use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Hall extends Model {

    use HasUuid;

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
