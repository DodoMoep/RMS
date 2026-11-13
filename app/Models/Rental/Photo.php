<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasUuids;

    protected $fillable = ['protocol_id', 'path', 'caption'];

    public function protocol()
    {
        return $this->belongsTo(Protocol::class);
    }
}
