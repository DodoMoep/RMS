<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Protocol extends Model {
    use HasUuid;
    protected $fillable=['rental_id','type','checklist','notes','meta','pdf_path','pdf_sha256'];
    protected $casts=['checklist'=>'array','meta'=>'array'];

    public function rental(){ return $this->belongsTo(Rental::class); }
    public function items(){ return $this->hasMany(ProtocolItem::class); }
    public function signatures(){ return $this->hasMany(Signature::class); }
    public function photos(){ return $this->hasMany(Photo::class); }
}
