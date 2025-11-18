<?php

namespace App\Http\Controllers;

use App\Models\Protocol;
use App\Models\Rental;

class DashboardController extends Controller
{
    public function index()
    {
        $todayRentals = collect();
        $openProtocols = collect();

        // Only load rental data if user has rental permissions
        if (auth()->user()->can('rentals.view')) {
            $today = now()->toDateString();

            $todayRentals = Rental::with(['tenant','hall'])
                ->whereDate('start', $today)
                ->orWhereDate('end', $today)
                ->orderBy('start')
                ->take(10)->get();
        }

        // Only load protocol data if user has protocol permissions
        if (auth()->user()->can('protocols.view')) {
            $openProtocols = Protocol::whereNull('pdf_path')->latest()->take(10)->get();
        }

        return view('dashboard', compact('todayRentals','openProtocols'));
    }
}
