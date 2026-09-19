<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStoryTenTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_renders_the_article_search_form(): void
    {
        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee('name="query"', false)
            ->assertSee(route('article.search'), false)
            ->assertSee('Cerca');
    }

    public function test_search_finds_accepted_articles_by_title(): void
    {
        $this->seed();

        $article = Article::factory()->accepted()->create([
            'title' => 'Bicicletta pieghevole unica',
            'description' => 'Mezzo di trasporto compatto.',
        ]);

        Article::factory()->accepted()->create([
            'title' => 'Tavolo da cucina',
            'description' => 'Legno massello.',
        ]);

        $this->get(route('article.search', ['query' => 'pieghevole']))
            ->assertOk()
            ->assertSee('Risultati per la ricerca')
            ->assertSee('pieghevole')
            ->assertSee($article->title)
            ->assertDontSee('Tavolo da cucina');
    }

    public function test_search_finds_accepted_articles_by_description(): void
    {
        $this->seed();

        $article = Article::factory()->accepted()->create([
            'title' => 'Oggetto misterioso',
            'description' => 'Questo annuncio contiene la parola climatizzatore.',
        ]);

        $this->get(route('article.search', ['query' => 'climatizzatore']))
            ->assertOk()
            ->assertSee($article->title)
            ->assertDontSee('Bici da corsa');
    }

    public function test_search_finds_accepted_articles_by_category_name(): void
    {
        $this->seed();

        $motori = Category::query()->where('name', 'Motori')->firstOrFail();

        $article = Article::factory()->accepted()->create([
            'title' => 'Casco integrale rarezza',
            'description' => 'Taglia M, visiera chiara.',
            'category_id' => $motori->id,
        ]);

        $this->get(route('article.search', ['query' => 'Motori']))
            ->assertOk()
            ->assertSee($article->title);
    }

    public function test_search_hides_pending_and_rejected_articles(): void
    {
        $this->seed();

        Article::factory()->pending()->create([
            'title' => 'Annuncio pending unico xyz',
            'description' => 'Ancora da revisionare.',
        ]);

        Article::factory()->rejected()->create([
            'title' => 'Annuncio rejected unico xyz',
            'description' => 'Rifiutato dal revisore.',
        ]);

        $this->get(route('article.search', ['query' => 'xyz']))
            ->assertOk()
            ->assertDontSee('Annuncio pending unico xyz')
            ->assertDontSee('Annuncio rejected unico xyz');
    }

    public function test_search_shows_empty_state_when_nothing_matches(): void
    {
        $this->seed();

        $this->get(route('article.search', ['query' => 'zzzznoresults']))
            ->assertOk()
            ->assertSee('Nessun articolo corrisponde alla tua ricerca');
    }

    public function test_search_results_escape_the_query_in_the_heading(): void
    {
        $this->seed();

        $this->get(route('article.search', ['query' => '<script>alert(1)</script>']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
