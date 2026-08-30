<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rental extends Model {
    use HasUuid;
    protected $fillable=['contact_id','hall_id','start','end','price','deposit','status'];
    protected $casts=['start'=>'datetime','end'=>'datetime'];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class, 'contact_id'); }

    public function hall(){ return $this->belongsTo(Hall::class); }
    public function protocols(){ return $this->hasMany(Protocol::class); }
    public function handover(){ return $this->hasOne(Protocol::class)->where('type','handover')->latest('id'); }
    public function returnProtocol(){ return $this->hasOne(Protocol::class)->where('type','return')->latest('id'); }
}
