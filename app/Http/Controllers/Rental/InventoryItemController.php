<?php
namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Models\Rental\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index()
    {
        $items = InventoryItem::latest()->paginate(15);
        return view('rental.inventory.index', compact('items'));
    }

    public function create() { return view('rental.inventory.create'); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'sku'=>'nullable|string|max:100',
            'description'=>'nullable|string'
        ]);
        InventoryItem::create($data);
        return redirect()->route('inventory-items.index')->with('ok','Inventarposition angelegt.');
    }

    public function edit(InventoryItem $inventoryItem)
    {
        return view('rental.inventory.edit', ['item'=>$inventoryItem]);
    }

    public function update(Request $r, InventoryItem $inventoryItem)
    {
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'sku'=>'nullable|string|max:100',
            'description'=>'nullable|string'
        ]);
        $inventoryItem->update($data);
        return redirect()->route('inventory-items.index')->with('ok','Inventar aktualisiert.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        if ($inventoryItem->halls()->exists()) {
            return back()->withErrors(['Inventarposition kann nicht gelöscht werden, da sie noch Hallen zugeordnet ist.']);
        }
        
        $inventoryItem->delete();
        return back()->with('ok','Inventar gelöscht.');
    }
}

