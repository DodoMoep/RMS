<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkInstruction extends Model
{
    use HasUuids;

    protected $fillable = ['category_id', 'title', 'pdf_path', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkInstructionCategory::class, 'category_id');
    }
}
