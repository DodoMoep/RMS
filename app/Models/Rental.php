<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Rental extends Model {
    use HasUuid;
    protected $fillable=['tenant_id','hall_id','start','end','price','deposit','status'];
    protected $casts=['start'=>'datetime','end'=>'datetime'];

    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function hall(){ return $this->belongsTo(Hall::class); }
    public function protocols(){ return $this->hasMany(Protocol::class); }
    public function handover(){ return $this->hasOne(Protocol::class)->ofMany(['id' => 'max'], function($q){ $q->where('type','handover'); }); }
    public function returnProtocol(){ return $this->hasOne(Protocol::class)->ofMany(['id' => 'max'], function($q){ $q->where('type','return'); }); }
}
