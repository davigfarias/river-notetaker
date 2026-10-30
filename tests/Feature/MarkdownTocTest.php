<?php

use App\Models\AccessToken;
use App\Models\Notes;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use App\Support\MarkdownToc;
use Illuminate\Support\Str;
use Livewire\Livewire;

const TOC_MD = "# Graça\n\nTexto.\n\n## Comum\n\n### Detalhe\n\n#### Fundo demais\n\n## Comum\n";

test('o índice lista os headings de # a ### com ids estáveis e únicos', function () {
    expect(MarkdownToc::headings(TOC_MD, 'campo'))->toBe([
        ['level' => 1, 'text' => 'Graça', 'id' => 'campo-graca'],
        ['level' => 2, 'text' => 'Comum', 'id' => 'campo-comum'],
        ['level' => 3, 'text' => 'Detalhe', 'id' => 'campo-detalhe'],
        ['level' => 2, 'text' => 'Comum', 'id' => 'campo-comum-2'],
    ]);
});

test('os ids do índice batem com os ids gravados no HTML', function () {
    $html = Str::markdownRich(TOC_MD, [], 'campo');

    foreach (MarkdownToc::headings(TOC_MD, 'campo') as $heading) {
        expect($html)->toContain('id="'.$heading['id'].'"');
    }

    expect($html)->not->toContain('campo-fundo-demais');
});

test('sem headings suficientes não há índice', function (?string $markdown) {
    expect(MarkdownToc::headings($markdown, 'campo'))->toBe([]);
})->with([
    'nulo' => [null],
    'vazio' => [''],
    'sem heading' => ['Só um parágrafo.'],
    'um heading só' => ["## Único\n\ntexto"],
    'só nível 4' => ["#### A\n\n#### B"],
    'jogo da velha dentro de código' => ["```\n# comentário\n## outro\n```"],
]);

test('um heading sem texto aproveitável ainda ganha id', function () {
    $headings = MarkdownToc::headings("## ???\n\n## !!!", 'campo');

    expect(array_column($headings, 'id'))->toBe(['campo-secao', 'campo-secao-2']);
});

test('markdownRich sem prefixo continua sem ids nos headings', function () {
    expect(Str::markdownRich("# Título\n\ntexto"))->not->toContain('id=');
});

test('a anotação de recurso mostra o índice só quando há headings', function () {
    $token = AccessToken::factory()->create();
    $material = ReferenceMaterial::factory()->create(['access_token_id' => $token->id]);
    ReadingNote::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        'title' => 'Com índice',
        'body' => "## Um\n\ntexto\n\n## Dois\n\ntexto",
    ]);
    ReadingNote::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        'title' => 'Sem índice',
        'body' => 'Sem nenhum heading aqui.',
    ]);

    $this->withSession(['access_token_id' => $token->id]);
    Livewire::withoutLazyLoading();

    Livewire::test('pages::referencia', ['id' => $material->id])
        ->assertSee('Índice (2)')
        ->assertDontSee('Índice (0)')
        ->assertSeeHtml('href="#anotacao-')
        ->assertSeeHtml('id="anotacao-');
});

test('a nota de aula mostra o índice dos campos com headings', function () {
    $token = AccessToken::factory()->create();
    $note = Notes::factory()->create([
        'access_token_id' => $token->id,
        'summary' => "# Resumo\n\n## Parte A\n\n## Parte B",
    ]);

    $this->withSession(['access_token_id' => $token->id])
        ->get(route('disciplinas.show', ['slug' => $note->discipline->slug, 'nota' => $note->id]))
        ->assertOk()
        ->assertSee('Índice (3)')
        ->assertSee('href="#summary-parte-a"', false)
        ->assertSee('id="summary-parte-a"', false);
});
