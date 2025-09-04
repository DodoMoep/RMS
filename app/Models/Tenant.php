<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Tenant extends Model {
    use HasUuid;
    protected $fillable=['name','email','phone'];
    public function rentals(){ return $this->hasMany(Rental::class); }
}
