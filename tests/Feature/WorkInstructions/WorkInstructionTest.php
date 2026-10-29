<?php

use App\Models\User;
use App\Models\WorkInstruction;
use App\Models\WorkInstructionCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeCategory(array $overrides = []): WorkInstructionCategory
{
    return WorkInstructionCategory::create(array_merge([
        'name' => 'Rasen mähen',
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides));
}

function makeInstruction(WorkInstructionCategory $category, array $overrides = [], string $content = '%PDF-1.4 Testinhalt'): WorkInstruction
{
    $path = "work-instructions/{$category->id}/".Str::uuid().'.pdf';
    Storage::disk('local')->put($path, $content);

    return $category->instructions()->create(array_merge([
        'title' => 'Sicherheitsunterweisung',
        'pdf_path' => $path,
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides));
}

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

// ------------------------------------------------------------------ Verwaltung

test('an uploaded work instruction is stored on the private disk only', function () {
    $admin = userWithPermissions(['work-instructions.manage']);
    $category = makeCategory();

    $this->actingAs($admin)
        ->post(route('work-instructions.instructions.store', $category), [
            'title' => 'Neue Anweisung',
            'pdf' => UploadedFile::fake()->create('anweisung.pdf', 50, 'application/pdf'),
            'sort_order' => 1,
            'is_active' => 1,
        ])
        ->assertRedirect(route('work-instructions.categories.edit', $category));

    $instruction = $category->instructions()->firstOrFail();

    Storage::disk('local')->assertExists($instruction->pdf_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('replacing the pdf removes the old private file and stores the new one privately', function () {
    $admin = userWithPermissions(['work-instructions.manage']);
    $category = makeCategory();
    $instruction = makeInstruction($category);
    $oldPath = $instruction->pdf_path;

    $this->actingAs($admin)
        ->put(route('work-instructions.instructions.update', [$category, $instruction]), [
            'title' => 'Aktualisiert',
            'pdf' => UploadedFile::fake()->create('neu.pdf', 50, 'application/pdf'),
            'sort_order' => 0,
            'is_active' => 1,
        ])
        ->assertRedirect();

    $instruction->refresh();

    expect($instruction->pdf_path)->not->toBe($oldPath)
        ->and($instruction->title)->toBe('Aktualisiert');
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($instruction->pdf_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('updating without a new pdf keeps the file', function () {
    $admin = userWithPermissions(['work-instructions.manage']);
    $category = makeCategory();
    $instruction = makeInstruction($category);

    $this->actingAs($admin)
        ->put(route('work-instructions.instructions.update', [$category, $instruction]), [
            'title' => 'Nur Titel geändert',
            'sort_order' => 3,
        ])
        ->assertRedirect();

    Storage::disk('local')->assertExists($instruction->refresh()->pdf_path);
    expect($instruction->title)->toBe('Nur Titel geändert');
});

test('deleting a work instruction removes the private file', function () {
    $admin = userWithPermissions(['work-instructions.manage']);
    $category = makeCategory();
    $instruction = makeInstruction($category);

    $this->actingAs($admin)
        ->delete(route('work-instructions.instructions.destroy', [$category, $instruction]))
        ->assertRedirect();

    $this->assertModelMissing($instruction);
    Storage::disk('local')->assertMissing($instruction->pdf_path);
});

test('the administration requires the manage permission', function () {
    $category = makeCategory();

    $this->actingAs(User::factory()->create())
        ->get(route('work-instructions.categories.index'))
        ->assertForbidden();
    $this->actingAs(User::factory()->create())
        ->post(route('work-instructions.instructions.store', $category), [
            'title' => 'Unberechtigt',
            'pdf' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            'sort_order' => 0,
        ])
        ->assertForbidden();

    expect($category->instructions()->count())->toBe(0);
});

// ----------------------------------------------------------- oeffentlicher Abruf

test('a work instruction is delivered inline without login', function () {
    $category = makeCategory();
    $instruction = makeInstruction($category, [], '%PDF-1.4 Inhalt der Anweisung');

    $response = $this->get(route('work-instructions.serve', [$category, $instruction]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->baseResponse->getFile()->getContent())->toBe('%PDF-1.4 Inhalt der Anweisung');
});

test('the public list shows only active instructions without login', function () {
    $category = makeCategory();
    makeInstruction($category, ['title' => 'Sichtbare Anweisung']);
    makeInstruction($category, ['title' => 'Verborgene Anweisung', 'is_active' => false]);

    $this->get(route('work-instructions.show', $category))
        ->assertOk()
        ->assertSee('Sichtbare Anweisung')
        ->assertDontSee('Verborgene Anweisung');
});

test('an inactive instruction cannot be retrieved', function () {
    $category = makeCategory();
    $instruction = makeInstruction($category, ['is_active' => false]);

    $this->get(route('work-instructions.serve', [$category, $instruction]))->assertNotFound();
});

test('an instruction of an inactive category cannot be retrieved', function () {
    $category = makeCategory(['is_active' => false]);
    $instruction = makeInstruction($category);

    $this->get(route('work-instructions.show', $category))->assertNotFound();
    $this->get(route('work-instructions.serve', [$category, $instruction]))->assertNotFound();
});

test('an instruction cannot be retrieved through a different category', function () {
    $category = makeCategory();
    $other = makeCategory(['name' => 'Andere Tätigkeit']);
    $instruction = makeInstruction($category);

    $this->get(route('work-instructions.serve', [$other, $instruction]))->assertNotFound();
});

test('a missing file gives 404 instead of an error', function () {
    $category = makeCategory();
    $instruction = makeInstruction($category);
    Storage::disk('local')->delete($instruction->pdf_path);

    $this->get(route('work-instructions.serve', [$category, $instruction]))->assertNotFound();
});

test('a file that only exists on the public disk is not delivered', function () {
    $category = makeCategory();
    $instruction = makeInstruction($category);
    Storage::disk('local')->delete($instruction->pdf_path);
    Storage::disk('public')->put($instruction->pdf_path, '%PDF-1.4 alt');

    $this->get(route('work-instructions.serve', [$category, $instruction]))->assertNotFound();
});
