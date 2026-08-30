<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class ProtocolItem extends Model {
    use HasUuid;
    protected $fillable=['protocol_id','inventory_item_id','label','state','comment','charge'];
    public function protocol(){ return $this->belongsTo(Protocol::class); }
    public function inventoryItem(){ return $this->belongsTo(InventoryItem::class); }
}
