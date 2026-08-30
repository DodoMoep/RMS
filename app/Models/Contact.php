<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'name',
        'contact_person_name',
        'email',
        'phone',
        'street',
        'zip_code',
        'city',
        'address_notes',
        'notes',
        'customer_number',
        'is_active',
    ];

    protected $casts = [
        'type'      => ContactType::class,
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Contact $contact) {
            if ($contact->type !== ContactType::Tenant && !$contact->customer_number) {
                $contact->customer_number = static::generateCustomerNumber();
            }
        });
    }

    public static function generateCustomerNumber(): string
    {
        $year = date('Y');
        $last = static::where('customer_number', 'like', "CUS-{$year}-%")
            ->orderByDesc('created_at')
            ->first();

        $newNumber = $last ? ((int) substr($last->customer_number, -4)) + 1 : 1;

        return sprintf('CUS-%s-%04d', $year, $newNumber);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTenants($query)
    {
        return $query->whereIn('type', ['tenant', 'both']);
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    // Relations
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'contact_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'contact_id');
    }

    // Accessor
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            trim(($this->zip_code ?? '') . ' ' . ($this->city ?? '')),
        ]);
        if ($this->address_notes) {
            $parts[] = $this->address_notes;
        }
        return implode("\n", $parts);
    }
}
