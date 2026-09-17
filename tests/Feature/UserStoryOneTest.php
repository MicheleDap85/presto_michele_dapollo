<?php

namespace Tests\Feature;

use App\Livewire\CreateArticleForm;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserStoryOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_insert_ad_button(): void
    {
        $this->seed();

        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee('Inserisci annuncio')
            ->assertSee('PRESTO');
    }

    public function test_guests_are_redirected_from_create_article_page(): void
    {
        $this->get(route('article.create'))
            ->assertRedirect(route('login'));
    }

    public function test_registration_redirects_to_create_article_page(): void
    {
        $this->seed();

        $this->post(route('register'), [
            'name' => 'Linda',
            'email' => 'linda@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/create');

        $this->assertAuthenticated();
    }

    public function test_authenticated_user_can_create_an_article(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CreateArticleForm::class)
            ->set('title', 'Bici da corsa')
            ->set('description', 'Bici in ottimo stato, usata poco.')
            ->set('price', 150)
            ->set('category', $category->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Bici da corsa',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertSame(1, Article::query()->count());
    }
}
