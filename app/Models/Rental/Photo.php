<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Photo extends Model {
    use HasUuids;
    protected $fillable=['protocol_id','path','caption'];
    public function protocol(){ return $this->belongsTo(Protocol::class); }
}
