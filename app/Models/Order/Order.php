<?php

namespace App\Models\Order;

use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_number',
        'contact_id',
        'delivery_date',
        'delivery_type',
        'status',
        'notes',
        'delivery_note_path',
        'delivery_note_printed_at',
        'created_by',
        'packed_by',
        'delivered_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'delivery_date' => 'date',
        'delivery_note_printed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderHistory::class);
    }

    public function scopeNew($query)
    {
        return $query->where('status', OrderStatus::NEW);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', OrderStatus::IN_PROGRESS);
    }

    public function scopePacked($query)
    {
        return $query->where('status', OrderStatus::PACKED);
    }

    public function scopeForPacker($query)
    {
        return $query->where('status', OrderStatus::IN_PROGRESS);
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, [OrderStatus::NEW, OrderStatus::IN_PROGRESS]);
    }

    public function canAddItems(): bool
    {
        return in_array($this->status, [OrderStatus::NEW, OrderStatus::IN_PROGRESS]);
    }

    public function isFullyPacked(): bool
    {
        return $this->items()->where('is_packed', false)->count() === 0;
    }

    public function lockOrder(): void
    {
        if (!$this->isFullyPacked()) {
            throw new \Exception('Cannot lock order. Not all items are packed.');
        }

        $this->update([
            'status' => OrderStatus::PACKED,
            'packed_by' => auth()->id(),
        ]);

        $this->logHistory('order_completed', 'Order completed and locked', [
            'packed_by' => auth()->user()?->name ?? 'system',
        ]);
    }

    public function generateDeliveryNote(): string
    {
        // Always generate delivery note in German
        $previousLocale = app()->getLocale();
        app()->setLocale('de');
        
        $pdf = \PDF::loadView('order.delivery-notes.template', ['order' => $this]);
        
        // Restore previous locale
        app()->setLocale($previousLocale);
        
        $filename = "delivery-note-{$this->order_number}.pdf";
        $path = "delivery-notes/{$filename}";
        
        \Storage::disk('public')->put($path, $pdf->output());
        
        $this->update(['delivery_note_path' => $path]);
        
        $this->logHistory('delivery_note_generated', 'Delivery note generated');
        
        return $path;
    }

    public function markAsInDelivery(): void
    {
        $this->update([
            'status' => OrderStatus::IN_DELIVERY,
            'delivery_note_printed_at' => now(),
        ]);

        $this->logHistory('delivery_note_printed', 'Delivery note printed, order marked as in delivery');
    }

    public function logHistory(string $eventType, string $description, array $metadata = []): void
    {
        $this->history()->create([
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function ordersByStatus($period = 30)
    {
        $query = static::select('status', DB::raw('count(*) as count'));
        
        if ($period !== 'all') {
            $query->where('created_at', '>=', now()->subDays((int)$period));
        }
        
        return $query->groupBy('status')->get();
    }

    public static function ordersOverTime(string $period = 'day', $days = 30)
    {
        // Whitelist to prevent SQL injection — $dateFormat must never be user-controlled
        $dateFormat = match($period) {
            'hour'  => '%Y-%m-%d %H:00',
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',   // 'day' and any other value fall back to date
        };

        $query = static::selectRaw('DATE_FORMAT(created_at, ?) as period, count(*) as count', [$dateFormat]);
        
        if ($days !== 'all') {
            $query->where('created_at', '>=', now()->subDays((int)$days));
        }
        
        return $query->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    public static function topItems(int $limit = 10, $period = 30)
    {
        $query = OrderItem::select(
            'article_id',
            'article_name',
            DB::raw('SUM(quantity_ordered) as total_quantity'),
            DB::raw('COUNT(DISTINCT order_id) as order_count')
        );
        
        if ($period !== 'all') {
            $query->whereHas('order', function($q) use ($period) {
                $q->where('created_at', '>=', now()->subDays((int)$period));
            });
        }
        
        return $query->groupBy('article_id', 'article_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    public static function packerPerformance($period = 30)
    {
        $query = static::select(
            'packed_by',
            'users.name as packer_name',
            DB::raw('COUNT(*) as orders_packed')
        )
        ->join('users', 'orders.packed_by', '=', 'users.id')
        ->whereNotNull('packed_by')
        ->whereIn('status', ['packed', 'in_delivery', 'delivered']);
        
        if ($period !== 'all') {
            $query->where('orders.created_at', '>=', now()->subDays((int)$period));
        }
        
        return $query->groupBy('packed_by', 'users.name')->get();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    protected static function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = static::where('created_at', '>=', now()->startOfYear())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastOrder && preg_match('/ORD-\d{4}-(\d+)/', $lastOrder->order_number, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        return sprintf('ORD-%s-%04d', $year, $number);
    }
}
