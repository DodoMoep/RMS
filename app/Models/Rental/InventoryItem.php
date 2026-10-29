<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InventoryItem extends Model {
    use HasUuids;
    protected $fillable=['name','sku','description'];
    
    public function halls(){
        return $this->belongsToMany(Hall::class,'hall_inventory')
            ->withPivot('quantity')->withTimestamps();
    }
}
