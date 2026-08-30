<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkInstructionCategory extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $category) {
            $base = Str::slug($category->name);
            $slug = $base;
            $i    = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $category->slug = $slug;
        });
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(WorkInstruction::class, 'category_id')
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public function activeInstructions(): HasMany
    {
        return $this->hasMany(WorkInstruction::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}
