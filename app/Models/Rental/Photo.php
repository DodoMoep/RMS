<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Photo extends Model {
    use HasUuid;
    protected $fillable=['protocol_id','path','caption'];
    public function protocol(){ return $this->belongsTo(Protocol::class); }
}
