<?php

namespace App\Http\Controllers;

use App\Models\Protocol;
use App\Models\Rental;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $todayRentals = Rental::with(['tenant','hall'])
            ->whereDate('start', $today)
            ->orWhereDate('end', $today)
            ->orderBy('start')
            ->take(10)->get();

        $openProtocols = Protocol::whereNull('pdf_path')->latest()->take(10)->get();

        return view('dashboard', compact('todayRentals','openProtocols'));
    }
}
