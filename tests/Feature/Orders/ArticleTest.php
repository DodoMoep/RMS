<?php

use App\Models\Order\Article;

/**
 * Artikelstamm (ArticleController).
 */
function articlePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Stehtisch',
        'sku' => 'STT-100',
        'description' => 'Hoher Tisch mit Husse',
        'price' => '24.90',
        'is_active' => '1',
    ], $overrides);
}

beforeEach(function () {
    $this->user = userWithPermissions(['orders.create']);
});

// ---------------------------------------------------------------- Anlegen

test('an article can be created', function () {
    $this->actingAs($this->user)
        ->post(route('articles.store'), articlePayload())
        ->assertRedirect(route('articles.index'))
        ->assertSessionHas('ok', __('articles.messages.created'));

    $article = Article::firstOrFail();

    expect($article->name)->toBe('Stehtisch')
        ->and($article->sku)->toBe('STT-100')
        ->and($article->description)->toBe('Hoher Tisch mit Husse')
        ->and((string) $article->price)->toBe('24.90')
        ->and($article->is_active)->toBeTrue();
});

test('only the name is required to create an article', function () {
    $this->actingAs($this->user)
        ->post(route('articles.store'), ['name' => 'Nur Name'])
        ->assertSessionHasNoErrors();

    expect(Article::where('name', 'Nur Name')->exists())->toBeTrue();
});

test('an article can be created as inactive', function () {
    $this->actingAs($this->user)
        ->post(route('articles.store'), articlePayload(['is_active' => '0']))
        ->assertSessionHasNoErrors();

    expect(Article::firstOrFail()->is_active)->toBeFalse();
});

test('invalid article data is rejected', function (array $overrides, string $errorField) {
    $this->actingAs($this->user)
        ->post(route('articles.store'), articlePayload($overrides))
        ->assertSessionHasErrors($errorField);

    expect(Article::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'sku too long' => [['sku' => str_repeat('a', 256)], 'sku'],
    'negative price' => [['price' => '-1'], 'price'],
    'price not a number' => [['price' => 'teuer'], 'price'],
    'active flag not boolean' => [['is_active' => 'vielleicht'], 'is_active'],
]);

test('the sku must be unique when creating an article', function () {
    makeArticle(['sku' => 'STT-100']);

    $this->actingAs($this->user)
        ->post(route('articles.store'), articlePayload(['sku' => 'STT-100']))
        ->assertSessionHasErrors('sku');

    expect(Article::count())->toBe(1);
});

// -------------------------------------------------------------- Bearbeiten

test('an article can be changed', function () {
    $article = makeArticle(['name' => 'Alt', 'sku' => 'ALT-1']);

    $this->actingAs($this->user)
        ->put(route('articles.update', $article), articlePayload(['name' => 'Neu', 'sku' => 'NEU-1', 'is_active' => '0']))
        ->assertRedirect(route('articles.index'))
        ->assertSessionHas('ok', __('articles.messages.updated'));

    $article->refresh();

    expect($article->name)->toBe('Neu')
        ->and($article->sku)->toBe('NEU-1')
        ->and($article->is_active)->toBeFalse();
});

test('an article may keep its own sku when it is changed', function () {
    $article = makeArticle(['sku' => 'STT-100']);

    $this->actingAs($this->user)
        ->put(route('articles.update', $article), articlePayload(['name' => 'Umbenannt', 'sku' => 'STT-100']))
        ->assertSessionHasNoErrors();

    expect($article->refresh()->name)->toBe('Umbenannt');
});

test('an article cannot take the sku of another article', function () {
    makeArticle(['sku' => 'BELEGT-1']);
    $article = makeArticle(['sku' => 'FREI-1']);

    $this->actingAs($this->user)
        ->put(route('articles.update', $article), articlePayload(['sku' => 'BELEGT-1']))
        ->assertSessionHasErrors('sku');

    expect($article->refresh()->sku)->toBe('FREI-1');
});

test('changing an article does not change existing order items', function () {
    $article = makeArticle(['name' => 'Alt', 'price' => 5]);
    $order = makeOrder(makeContact(), $this->user);
    $item = $order->items()->create([
        'article_id' => $article->id, 'article_name' => 'Alt', 'article_price' => 5, 'quantity_ordered' => 1,
    ]);

    $this->actingAs($this->user)
        ->put(route('articles.update', $article), articlePayload(['name' => 'Neu', 'price' => '99', 'sku' => null]));

    expect($item->refresh()->article_name)->toBe('Alt')
        ->and((string) $item->article_price)->toBe('5.00');
});

// ----------------------------------------------------------------- Loeschen

test('an unused article is deleted but kept as soft delete', function () {
    $article = makeArticle();

    $this->actingAs($this->user)
        ->delete(route('articles.destroy', $article))
        ->assertRedirect(route('articles.index'))
        ->assertSessionHas('ok', __('articles.messages.deleted'));

    expect(Article::find($article->id))->toBeNull()
        ->and(Article::withTrashed()->find($article->id))->not->toBeNull();
});

test('an article that is used in an order cannot be deleted', function () {
    $article = makeArticle();
    $order = makeOrder(makeContact(), $this->user);
    $order->items()->create(['article_id' => $article->id, 'article_name' => $article->name, 'quantity_ordered' => 1]);

    $this->actingAs($this->user)
        ->delete(route('articles.destroy', $article))
        ->assertRedirect();

    expect(session('errors')->first())->toBe(__('articles.messages.in_use'))
        ->and(Article::find($article->id))->not->toBeNull();
});

// -------------------------------------------------------------- Seiten, Rechte

test('the article list shows articles and is paginated', function () {
    foreach (range(1, 21) as $i) {
        makeArticle(['name' => sprintf('Artikel %02d', $i)]);
    }

    $first = $this->actingAs($this->user)->get(route('articles.index'))->assertOk();
    $second = $this->actingAs($this->user)->get(route('articles.index', ['page' => 2]));

    expect($first->viewData('articles'))->toHaveCount(20)
        ->and($second->viewData('articles'))->toHaveCount(1);
});

test('the article forms render', function () {
    $article = makeArticle(['name' => 'Formularartikel']);

    $this->actingAs($this->user)->get(route('articles.create'))->assertOk();
    $this->actingAs($this->user)->get(route('articles.edit', $article))->assertOk()->assertSee('Formularartikel');
});

test('every article route needs the create permission', function () {
    $viewer = userWithPermissions(['orders.view']);
    $article = makeArticle();

    $this->actingAs($viewer)->get(route('articles.index'))->assertForbidden();
    $this->actingAs($viewer)->get(route('articles.create'))->assertForbidden();
    $this->actingAs($viewer)->post(route('articles.store'), articlePayload())->assertForbidden();
    $this->actingAs($viewer)->get(route('articles.edit', $article))->assertForbidden();
    $this->actingAs($viewer)->put(route('articles.update', $article), articlePayload())->assertForbidden();
    $this->actingAs($viewer)->delete(route('articles.destroy', $article))->assertForbidden();

    expect(Article::count())->toBe(1)
        ->and($article->refresh()->name)->toBe('Testartikel');
});
