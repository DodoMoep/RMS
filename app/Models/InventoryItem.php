<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class InventoryItem extends Model {
    use HasUuid;
    protected $fillable=['name','sku','description'];
}
