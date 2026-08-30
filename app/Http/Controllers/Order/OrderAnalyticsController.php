<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\Order\Order;
use Illuminate\Http\Request;

class OrderAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['period' => 'nullable|in:7,14,30,60,90,180,365,all']);

        $period = $request->get('period', 30);
        
        // Build base query with date filter
        $query = Order::query();
        if ($period !== 'all') {
            $query->where('created_at', '>=', now()->subDays((int)$period));
        }
        
        $totalOrders = (clone $query)->count();
        $activeOrders = (clone $query)->whereIn('status', [OrderStatus::NEW->value, OrderStatus::IN_PROGRESS->value])->count();
        
        // Items packed in the period
        $packedStatuses = [OrderStatus::PACKED->value, OrderStatus::IN_DELIVERY->value, OrderStatus::DELIVERED->value];
        $itemsPacked = \App\Models\Order\OrderItem::whereHas('order', function($q) use ($period, $packedStatuses) {
            $q->whereIn('status', $packedStatuses);
            if ($period !== 'all') {
                $q->where('created_at', '>=', now()->subDays((int)$period));
            }
        })->count();

        $statusData = Order::ordersByStatus($period);
        $timeSeriesData = Order::ordersOverTime('day', $period);
        $topItemsData = Order::topItems(10, $period);
        $packerPerformance = Order::packerPerformance($period);

        return view('order.analytics.index', compact(
            'totalOrders',
            'activeOrders',
            'itemsPacked',
            'statusData',
            'timeSeriesData',
            'topItemsData',
            'packerPerformance',
            'period'
        ));
    }

    public function ordersByStatus()
    {
        return response()->json(Order::ordersByStatus());
    }

    public function ordersOverTime(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:hour,day,week,month',
            'days'   => 'nullable|integer|min:1|max:365',
        ]);

        $period = $request->get('period', 'day');
        $days   = (int) $request->get('days', 30);

        return response()->json(Order::ordersOverTime($period, $days));
    }

    public function topItems(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = (int) $request->get('limit', 10);
        return response()->json(Order::topItems($limit));
    }

    public function packerPerformance()
    {
        return response()->json(Order::packerPerformance());
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'period' => 'nullable|integer|min:1|max:365',
        ]);

        $period = (int) $request->get('period', 30);

        $data = [
            'totalOrders' => Order::count(),
            'statusData' => Order::ordersByStatus(),
            'topItems' => Order::topItems(10),
            'packerPerformance' => Order::packerPerformance(),
            'generatedAt' => now(),
        ];

        $pdf = \PDF::loadView('order.analytics.report-pdf', $data);
        return $pdf->download('order-analytics-' . now()->format('Y-m-d') . '.pdf');
    }
}


