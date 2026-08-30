<?php

namespace App\Http\Controllers;

use App\Models\WorkInstruction;
use App\Models\WorkInstructionCategory;
use Illuminate\Support\Facades\Storage;

class WorkInstructionPublicController extends Controller
{
    public function show(WorkInstructionCategory $category)
    {
        abort_unless($category->is_active, 404);

        $instructions = $category->activeInstructions()->get();

        return view('public.work-instructions.show', compact('category', 'instructions'));
    }

    public function serve(WorkInstructionCategory $category, WorkInstruction $instruction)
    {
        abort_unless($category->is_active && $instruction->is_active, 404);
        abort_unless($instruction->category_id === $category->id, 404);

        $path = Storage::disk('public')->path($instruction->pdf_path);

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($instruction->title) . '.pdf"',
        ]);
    }
}
