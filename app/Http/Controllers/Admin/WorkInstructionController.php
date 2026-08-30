<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkInstructionCategory;
use App\Models\WorkInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkInstructionController extends Controller
{
    public function create(WorkInstructionCategory $category)
    {
        return view('admin.work-instructions.instructions.create', compact('category'));
    }

    public function store(Request $request, WorkInstructionCategory $category)
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'pdf'        => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $path = $request->file('pdf')->storeAs(
            'work-instructions/' . $category->id,
            Str::uuid() . '.pdf',
            'public'
        );

        $category->instructions()->create([
            'title'      => $data['title'],
            'pdf_path'   => $path,
            'sort_order' => $data['sort_order'],
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('work-instructions.categories.edit', $category)
            ->with('status', __('work_instructions.instruction_created'));
    }

    public function edit(WorkInstructionCategory $category, WorkInstruction $instruction)
    {
        return view('admin.work-instructions.instructions.edit', compact('category', 'instruction'));
    }

    public function update(Request $request, WorkInstructionCategory $category, WorkInstruction $instruction)
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'pdf'        => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $pdfPath = $instruction->pdf_path;

        if ($request->hasFile('pdf')) {
            Storage::disk('public')->delete($instruction->pdf_path);

            $pdfPath = $request->file('pdf')->storeAs(
                'work-instructions/' . $category->id,
                Str::uuid() . '.pdf',
                'public'
            );
        }

        $instruction->update([
            'title'      => $data['title'],
            'pdf_path'   => $pdfPath,
            'sort_order' => $data['sort_order'],
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('work-instructions.categories.edit', $category)
            ->with('status', __('work_instructions.instruction_updated'));
    }

    public function destroy(WorkInstructionCategory $category, WorkInstruction $instruction)
    {
        Storage::disk('public')->delete($instruction->pdf_path);
        $instruction->delete();

        return redirect()->route('work-instructions.categories.edit', $category)
            ->with('status', __('work_instructions.instruction_deleted'));
    }
}
