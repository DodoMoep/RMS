<?php

namespace App\Models\Order;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

class OrderHistory extends Model
{
    use HasUuids;

    protected $table = 'order_history';

    protected $fillable = [
        'order_id',
        'user_id',
        'event_type',
        'old_value',
        'new_value',
        'metadata',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Beschreibung in der gewaehlten Sprache. Gespeichert wird ein englischer Text; die Anzeige
     * baut den Satz aus Ereignistyp und Metadaten neu. Fehlt ein Wert oder ist der Typ
     * unbekannt (z. B. bei alten Eintraegen), kommt der gespeicherte Text zurueck.
     */
    public function localizedDescription(?string $orderNumber = null): string
    {
        $meta = $this->metadata ?? [];

        $params = match ($this->event_type) {
            'order_created', 'order_deleted' => $orderNumber !== null ? ['number' => $orderNumber] : null,
            'status_changed' => isset($meta['old_status'], $meta['new_status']) ? [
                'from' => __('orders.statuses.'.$meta['old_status']),
                'to' => __('orders.statuses.'.$meta['new_status']),
            ] : null,
            'item_packed' => isset($meta['item_name'], $meta['quantity_packed']) ? [
                'item' => $meta['item_name'],
                'quantity' => $meta['quantity_packed'],
            ] : null,
            'item_unpacked' => isset($meta['item_name']) ? ['item' => $meta['item_name']] : null,
            'order_completed', 'delivery_note_generated', 'delivery_note_printed' => [],
            default => null,
        };

        $key = 'orders.history_events.'.$this->event_type;

        if ($params === null || ! Lang::has($key)) {
            return (string) $this->description;
        }

        return __($key, $params);
    }

    /**
     * Bezeichnung des Ereignistyps in der gewaehlten Sprache, sonst der Rohwert.
     */
    public function localizedEventType(): string
    {
        $key = 'orders.history_event_types.'.$this->event_type;

        return Lang::has($key) ? __($key) : $this->event_type;
    }

    public function scopeByEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
