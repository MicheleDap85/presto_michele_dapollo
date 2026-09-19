<?php

namespace Tests\Feature;

use App\Livewire\CreateArticleForm;
use App\Mail\BecomeRevisor;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class UserStoryThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_makes_a_registered_user_a_revisor(): void
    {
        $user = User::factory()->create([
            'email' => 'pablo@example.com',
        ]);

        $this->artisan('app:make-user-revisor', ['email' => 'pablo@example.com'])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->is_revisor);
    }

    public function test_artisan_command_fails_when_the_email_does_not_match_a_user(): void
    {
        $this->artisan('app:make-user-revisor', ['email' => 'missing@example.com'])
            ->assertFailed();
    }

    public function test_guests_are_redirected_from_the_revisor_dashboard(): void
    {
        $this->get(route('revisor.index'))
            ->assertRedirect(route('homepage'))
            ->assertSessionHas('errorMessage', 'Zona riservata ai revisori');
    }

    public function test_authenticated_users_who_are_not_revisors_cannot_access_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('revisor.index'))
            ->assertRedirect(route('homepage'))
            ->assertSessionHas('errorMessage', 'Zona riservata ai revisori');
    }

    public function test_revisors_see_the_oldest_pending_article_one_at_a_time(): void
    {
        $revisor = User::factory()->revisor()->create();

        $older = Article::factory()->pending()->create([
            'title' => 'Annuncio più vecchio',
            'created_at' => now()->subDays(2),
        ]);

        Article::factory()->pending()->create([
            'title' => 'Annuncio più nuovo',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($revisor)
            ->get(route('revisor.index'))
            ->assertOk()
            ->assertSee('Zona revisore')
            ->assertSee($older->title)
            ->assertSee('Accetta annuncio')
            ->assertSee('Rifiuta annuncio')
            ->assertDontSee('Annuncio più nuovo');
    }

    public function test_revisor_can_accept_an_article(): void
    {
        $revisor = User::factory()->revisor()->create();
        $article = Article::factory()->pending()->create([
            'title' => 'Bici da accettare',
        ]);

        $this->actingAs($revisor)
            ->patch(route('revisor.accept', $article))
            ->assertRedirect()
            ->assertSessionHas('message', "Hai accettato l'articolo Bici da accettare");

        $this->assertTrue($article->fresh()->is_accepted);
    }

    public function test_revisor_can_reject_an_article(): void
    {
        $revisor = User::factory()->revisor()->create();
        $article = Article::factory()->pending()->create([
            'title' => 'Bici da rifiutare',
        ]);

        $this->actingAs($revisor)
            ->patch(route('revisor.reject', $article))
            ->assertRedirect()
            ->assertSessionHas('message', "Hai rifiutato l'articolo Bici da rifiutare");

        $this->assertFalse($article->fresh()->is_accepted);
    }

    public function test_revisor_can_undo_the_last_review_operation(): void
    {
        $revisor = User::factory()->revisor()->create();
        $article = Article::factory()->pending()->create([
            'title' => 'Annuncio da ripristinare',
        ]);

        $this->actingAs($revisor)
            ->patch(route('revisor.accept', $article));

        $this->actingAs($revisor)
            ->patch(route('revisor.undo'))
            ->assertRedirect()
            ->assertSessionHas('message', 'Ultima operazione annullata per Annuncio da ripristinare');

        $this->assertNull($article->fresh()->is_accepted);
    }

    public function test_guests_are_redirected_from_the_work_with_us_page(): void
    {
        $this->get(route('become.revisor'))
            ->assertRedirect(route('login'));
    }

    public function test_registered_user_can_request_to_become_revisor(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Pablo',
            'email' => 'pablo@example.com',
        ]);

        $this->actingAs($user)
            ->from(route('become.revisor'))
            ->post(route('become.revisor.submit'), [
                'motivation' => 'Vorrei collaborare con PRESTO',
            ])
            ->assertRedirect(route('homepage'))
            ->assertSessionHas('message', 'Complimenti, hai richiesto di diventare revisore');

        Mail::assertSent(BecomeRevisor::class, function (BecomeRevisor $mail) use ($user) {
            return $mail->user->is($user)
                && $mail->motivation === 'Vorrei collaborare con PRESTO'
                && $mail->hasTo('admin@presto.it');
        });
    }

    public function test_make_revisor_link_from_email_promotes_the_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Pablo',
        ]);

        $this->get(route('make.revisor', $user))
            ->assertRedirect(route('homepage'));

        $this->assertTrue($user->fresh()->is_revisor);
    }

    public function test_public_pages_show_only_accepted_articles(): void
    {
        $this->seed();

        $category = Category::query()->firstOrFail();
        $accepted = Article::factory()->accepted()->create([
            'title' => 'Annuncio visibile',
            'category_id' => $category->id,
        ]);
        $pending = Article::factory()->pending()->create([
            'title' => 'Annuncio in attesa',
            'category_id' => $category->id,
        ]);
        $rejected = Article::factory()->rejected()->create([
            'title' => 'Annuncio rifiutato',
            'category_id' => $category->id,
        ]);

        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee($accepted->title)
            ->assertDontSee($pending->title)
            ->assertDontSee($rejected->title);

        $this->get(route('article.index'))
            ->assertOk()
            ->assertSee($accepted->title)
            ->assertDontSee($pending->title)
            ->assertDontSee($rejected->title);

        $this->get(route('article.byCategory', $category))
            ->assertOk()
            ->assertSee($accepted->title)
            ->assertDontSee($pending->title)
            ->assertDontSee($rejected->title);

        $this->get(route('article.show', $pending))->assertNotFound();
        $this->get(route('article.show', $rejected))->assertNotFound();
        $this->get(route('article.show', $accepted))->assertOk()->assertSee($accepted->title);
    }

    public function test_new_articles_are_pending_until_a_revisor_accepts_them(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CreateArticleForm::class)
            ->set('title', 'Tavolo da campeggio')
            ->set('description', 'Tavolo pieghevole in ottime condizioni.')
            ->set('price', 40)
            ->set('category', $category->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Tavolo da campeggio',
            'is_accepted' => null,
        ]);
    }
}
