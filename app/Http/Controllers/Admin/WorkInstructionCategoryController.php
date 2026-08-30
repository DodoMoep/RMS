<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkInstructionCategory;
use Illuminate\Http\Request;

class WorkInstructionCategoryController extends Controller
{
    public function index()
    {
        $categories = WorkInstructionCategory::withCount('instructions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.work-instructions.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.work-instructions.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        WorkInstructionCategory::create($data);

        return redirect()->route('work-instructions.categories.index')
            ->with('status', __('work_instructions.category_created'));
    }

    public function edit(WorkInstructionCategory $category)
    {
        $category->load(['instructions' => fn ($q) => $q->orderBy('sort_order')->orderBy('title')]);

        return view('admin.work-instructions.categories.edit', compact('category'));
    }

    public function update(Request $request, WorkInstructionCategory $category)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()->route('work-instructions.categories.index')
            ->with('status', __('work_instructions.category_updated'));
    }

    public function destroy(WorkInstructionCategory $category)
    {
        if ($category->instructions()->exists()) {
            return back()->with('error', __('work_instructions.category_has_instructions'));
        }

        $category->delete();

        return redirect()->route('work-instructions.categories.index')
            ->with('status', __('work_instructions.category_deleted'));
    }
}
