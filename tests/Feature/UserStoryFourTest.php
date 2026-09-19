<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStoryFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_shows_italian_english_and_spanish_flags(): void
    {
        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee('vendor/blade-flags/country-it.svg', false)
            ->assertSee('vendor/blade-flags/country-uk.svg', false)
            ->assertSee('vendor/blade-flags/country-es.svg', false);
    }

    public function test_homepage_uses_italian_by_default(): void
    {
        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee('Ultimi annunci')
            ->assertSee('Inserisci annuncio')
            ->assertDontSee('Latest ads')
            ->assertDontSee('Últimos anuncios');
    }

    public function test_selecting_english_translates_static_ui_and_categories(): void
    {
        $this->seed();

        $this->from(route('homepage'))
            ->post(route('setLocale', 'uk'))
            ->assertRedirect(route('homepage'));

        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee('Latest ads')
            ->assertSee('Post an ad')
            ->assertSee('Electronics')
            ->assertDontSee('Ultimi annunci');

        $category = Category::query()->where('name', 'Elettronica')->firstOrFail();

        $this->get(route('article.byCategory', $category))
            ->assertOk()
            ->assertSee('Ads in Electronics')
            ->assertDontSee('Annunci in Elettronica');
    }

    public function test_selecting_spanish_translates_the_homepage(): void
    {
        $this->from(route('homepage'))
            ->post(route('setLocale', 'es'))
            ->assertRedirect(route('homepage'));

        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee('Últimos anuncios')
            ->assertSee('Publicar anuncio')
            ->assertDontSee('Ultimi annunci');
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->from(route('homepage'))
            ->post(route('setLocale', 'fr'))
            ->assertNotFound();
    }
}
