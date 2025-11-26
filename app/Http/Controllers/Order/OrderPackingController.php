<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderPackingController extends Controller
{
    public function index()
    {
        $orders = Order::forPacker()
            ->with(['creator', 'items', 'customer'])
            ->get()
            ->sort(function($a, $b) {
                // First sort by delivery_date (ascending - earliest first)
                $dateA = $a->delivery_date ? $a->delivery_date->timestamp : PHP_INT_MAX;
                $dateB = $b->delivery_date ? $b->delivery_date->timestamp : PHP_INT_MAX;
                
                if ($dateA !== $dateB) {
                    return $dateA <=> $dateB;
                }
                
                // If delivery dates are equal, sort by packing progress (ascending - least packed first)
                $totalItemsA = $a->items->count();
                $packedItemsA = $a->items->where('is_packed', true)->count();
                $progressA = $totalItemsA > 0 ? ($packedItemsA / $totalItemsA) : 0;
                
                $totalItemsB = $b->items->count();
                $packedItemsB = $b->items->where('is_packed', true)->count();
                $progressB = $totalItemsB > 0 ? ($packedItemsB / $totalItemsB) : 0;
                
                return $progressA <=> $progressB;
            });

        // Paginate manually after sorting
        $currentPage = request()->get('page', 1);
        $perPage = 15;
        $paginatedOrders = new \Illuminate\Pagination\LengthAwarePaginator(
            $orders->forPage($currentPage, $perPage),
            $orders->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('order.packing.index', ['orders' => $paginatedOrders]);
    }

    public function show(Order $order)
    {
        if ($order->status->value !== 'in_progress') {
            return redirect()->route('packing.index')
                ->withErrors(['Diese Bestellung kann nicht verpackt werden.']);
        }

        $order->load(['items.article', 'creator']);

        return view('order.packing.show', compact('order'));
    }

    public function packItem(Request $request, OrderItem $orderItem)
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        try {
            $quantity = $validated['quantity'] ?? null;
            $orderItem->markAsPacked($quantity);

            return back()->with('ok', 'Artikel verpackt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function unpackItem(OrderItem $orderItem)
    {
        try {
            $orderItem->unpack();
            return back()->with('ok', 'Artikel entpackt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function completeOrder(Order $order)
    {
        try {
            $order->lockOrder();
            
            return redirect()->route('orders.show', $order)
                ->with('ok', 'Bestellung abgeschlossen und gesperrt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }
}


