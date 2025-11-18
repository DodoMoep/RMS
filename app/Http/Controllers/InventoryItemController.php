<?php
namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index()
    {
        $items = InventoryItem::latest()->paginate(15);
        return view('inventory.index', compact('items'));
    }

    public function create() { return view('inventory.create'); }

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

    public function edit(InventoryItem $inventory_item)
    {
        return view('inventory.edit', ['item'=>$inventory_item]);
    }

    public function update(Request $r, InventoryItem $inventory_item)
    {
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'sku'=>'nullable|string|max:100',
            'description'=>'nullable|string'
        ]);
        $inventory_item->update($data);
        return redirect()->route('inventory-items.index')->with('ok','Inventar aktualisiert.');
    }

    public function destroy(InventoryItem $inventory_item)
    {
        if ($inventory_item->halls()->exists()) {
            return back()->withErrors(['Inventarposition kann nicht gelöscht werden, da sie noch Hallen zugeordnet ist.']);
        }
        
        $inventory_item->delete();
        return back()->with('ok','Inventar gelöscht.');
    }
}
