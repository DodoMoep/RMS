<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Rental extends Model {
    use HasUuids;
    protected $fillable=['contact_id','hall_id','start','end','price','deposit','status'];
    protected $casts=['start'=>'datetime','end'=>'datetime'];

    /**
     * Buchungen, die sich mit dem Zeitraum ueberschneiden. Die Intervalle sind halboffen:
     * Endet eine Buchung genau, wenn die naechste beginnt, ist das keine Ueberschneidung.
     * Die Eingaben werden zu Carbon geparst, damit der Vergleich auf jeder Datenbank
     * gleich arbeitet (auch bei "2026-11-10T10:00" aus dem Formular).
     */
    public function scopeOverlapping($query, $start, $end)
    {
        return $query
            ->where('start', '<', Carbon::parse($end))
            ->where('end', '>', Carbon::parse($start));
    }

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class, 'contact_id'); }

    public function hall(){ return $this->belongsTo(Hall::class); }
    public function protocols(){ return $this->hasMany(Protocol::class); }
    public function handover(){ return $this->hasOne(Protocol::class)->where('type','handover')->latest('id'); }
    public function returnProtocol(){ return $this->hasOne(Protocol::class)->where('type','return')->latest('id'); }
}
