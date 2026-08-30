<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class InventoryItem extends Model {
    use HasUuid;
    protected $fillable=['name','sku','description'];
    
    public function halls(){
        return $this->belongsToMany(Hall::class,'hall_inventory')
            ->withPivot('quantity')->withTimestamps();
    }
}
