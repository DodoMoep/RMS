<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 30);

        $totalOrders = Order::count();
        $activeOrders = Order::whereIn('status', [OrderStatus::NEW, OrderStatus::IN_PROGRESS])->count();
        $itemsPacked = Order::whereIn('status', [OrderStatus::PACKED, OrderStatus::IN_DELIVERY, OrderStatus::DELIVERED])
            ->whereMonth('created_at', now()->month)
            ->withCount('items')
            ->get()
            ->sum('items_count');

        $statusData = Order::ordersByStatus();
        $timeSeriesData = Order::ordersOverTime('day', $period);
        $topItemsData = Order::topItems(10);
        $packerPerformance = Order::packerPerformance();

        return view('analytics.index', compact(
            'totalOrders',
            'activeOrders',
            'itemsPacked',
            'statusData',
            'timeSeriesData',
            'topItemsData',
            'packerPerformance'
        ));
    }

    public function ordersByStatus()
    {
        return response()->json(Order::ordersByStatus());
    }

    public function ordersOverTime(Request $request)
    {
        $period = $request->get('period', 'day');
        $days = $request->get('days', 30);

        return response()->json(Order::ordersOverTime($period, $days));
    }

    public function topItems(Request $request)
    {
        $limit = $request->get('limit', 10);
        return response()->json(Order::topItems($limit));
    }

    public function packerPerformance()
    {
        return response()->json(Order::packerPerformance());
    }

    public function exportReport(Request $request)
    {
        $period = $request->get('period', 30);

        $data = [
            'totalOrders' => Order::count(),
            'statusData' => Order::ordersByStatus(),
            'topItems' => Order::topItems(10),
            'packerPerformance' => Order::packerPerformance(),
            'generatedAt' => now(),
        ];

        $pdf = \PDF::loadView('analytics.report-pdf', $data);
        return $pdf->download('order-analytics-' . now()->format('Y-m-d') . '.pdf');
    }
}

