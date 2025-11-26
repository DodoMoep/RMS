<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::latest()->paginate(15);
        return view('rental.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('rental.tenants.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'email'=>'nullable|email',
            'phone'=>'nullable|string|max:100',
        ]);
        Tenant::create($data);
        return redirect()->route('tenants.index')->with('ok','Mieter angelegt.');
    }

    public function edit(Tenant $tenant)
    {
        return view('rental.tenants.edit', compact('tenant'));
    }

    public function update(Request $r, Tenant $tenant)
    {
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'email'=>'nullable|email',
            'phone'=>'nullable|string|max:100',
        ]);
        $tenant->update($data);
        return redirect()->route('tenants.index')->with('ok','Mieter aktualisiert.');
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->rentals()->exists()) {
            return back()->withErrors(['Mieter kann nicht gelöscht werden, da noch Vermietungen vorhanden sind.']);
        }
        
        $tenant->delete();
        return back()->with('ok','Mieter gelöscht.');
    }
}

