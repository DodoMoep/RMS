<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Signature extends Model {
    use HasUuid;
    protected $fillable=['protocol_id','role','signer_name','png_path','signed_at'];
    protected $casts=['signed_at'=>'datetime'];
    public function protocol(){ return $this->belongsTo(Protocol::class); }
}
