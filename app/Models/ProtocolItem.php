<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class ProtocolItem extends Model {
    use HasUuid;
    protected $fillable=['protocol_id','inventory_item_id','label','state','comment','charge'];
    public function protocol(){ return $this->belongsTo(Protocol::class); }
}
