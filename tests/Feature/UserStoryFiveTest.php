<?php

namespace Tests\Feature;

use App\Jobs\GoogleVisionLabelImage;
use App\Jobs\GoogleVisionSafeSearch;
use App\Jobs\RemoveFaces;
use App\Jobs\ResizeImage;
use App\Livewire\CreateArticleForm;
use App\Models\Article;
use App\Models\Category;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserStoryFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_shows_a_multiple_image_input(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('article.create'))
            ->assertOk()
            ->assertSee('wire:model.live="temporary_images"', false)
            ->assertSee('multiple', false);
    }

    public function test_authenticated_user_can_attach_multiple_images_to_an_article(): void
    {
        Queue::fake([
            RemoveFaces::class,
            ResizeImage::class,
            GoogleVisionSafeSearch::class,
            GoogleVisionLabelImage::class,
        ]);
        Storage::fake('public');
        $this->seed();

        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();
        $firstImage = UploadedFile::fake()->image('bici.jpg');
        $secondImage = UploadedFile::fake()->image('sella.png');

        Livewire::actingAs($user)
            ->test(CreateArticleForm::class)
            ->set('title', 'Bici da corsa')
            ->set('description', 'Bici in ottimo stato, usata poco.')
            ->set('price', 150)
            ->set('category', $category->id)
            ->set('temporary_images', [$firstImage, $secondImage])
            ->assertSee('img-preview', false)
            ->assertSee('background-image:', false)
            ->assertSee('removeImage', false)
            ->call('store')
            ->assertHasNoErrors();

        $article = Article::query()->where('title', 'Bici da corsa')->firstOrFail();

        $this->assertSame(2, $article->images()->count());

        foreach ($article->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertStringStartsWith("articles/{$article->id}/", $image->path);
        }
    }

    public function test_a_single_preview_image_can_be_removed_before_saving(): void
    {
        Queue::fake([
            RemoveFaces::class,
            ResizeImage::class,
            GoogleVisionSafeSearch::class,
            GoogleVisionLabelImage::class,
        ]);
        Storage::fake('public');
        $this->seed();

        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CreateArticleForm::class)
            ->set('title', 'Tavolo in legno')
            ->set('description', 'Tavolo rustico in ottime condizioni.')
            ->set('price', 80)
            ->set('category', $category->id)
            ->set('temporary_images', [
                UploadedFile::fake()->image('tavolo-1.jpg'),
                UploadedFile::fake()->image('tavolo-2.jpg'),
            ])
            ->call('removeImage', 0)
            ->call('store')
            ->assertHasNoErrors();

        $article = Article::query()->where('title', 'Tavolo in legno')->firstOrFail();

        $this->assertSame(1, $article->images()->count());
        Storage::disk('public')->assertExists($article->images->first()->path);
    }

    public function test_store_attaches_images_from_the_temporary_upload_when_the_preview_collection_is_empty(): void
    {
        Queue::fake([
            RemoveFaces::class,
            ResizeImage::class,
            GoogleVisionSafeSearch::class,
            GoogleVisionLabelImage::class,
        ]);
        Storage::fake('public');
        $this->seed();

        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CreateArticleForm::class)
            ->set('title', 'Bici da corsa')
            ->set('description', 'Bici in ottimo stato, usata poco.')
            ->set('price', 150)
            ->set('category', $category->id)
            ->set('temporary_images', [UploadedFile::fake()->image('bici.jpg')])
            ->set('images', [])
            ->call('store')
            ->assertHasNoErrors();

        $article = Article::query()->where('title', 'Bici da corsa')->firstOrFail();

        $this->assertSame(1, $article->images()->count());
        Storage::disk('public')->assertExists($article->images->first()->path);
    }

    public function test_more_than_six_images_are_rejected(): void
    {
        Storage::fake('public');
        $this->seed();

        $images = [];
        for ($i = 1; $i <= 7; $i++) {
            $images[] = UploadedFile::fake()->image("foto-{$i}.jpg");
        }

        Livewire::actingAs(User::factory()->create())
            ->test(CreateArticleForm::class)
            ->set('temporary_images', $images)
            ->assertHasErrors(['temporary_images']);

        $this->assertSame(0, Image::query()->count());
    }

    public function test_non_image_files_are_rejected(): void
    {
        Storage::fake('public');
        $this->seed();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateArticleForm::class)
            ->set('temporary_images', [UploadedFile::fake()->create('manuale.pdf', 200, 'application/pdf')])
            ->assertHasErrors(['temporary_images.0']);

        $this->assertSame(0, Image::query()->count());
    }

    public function test_article_card_and_detail_use_stored_images(): void
    {
        Storage::fake('public');
        $this->seed();

        $article = Article::factory()->accepted()->create([
            'title' => 'Lampada vintage',
        ]);
        Image::factory()->for($article)->create(['path' => 'images/lampada-1.jpg']);
        Image::factory()->for($article)->create(['path' => 'images/lampada-2.jpg']);

        $this->get(route('article.index'))
            ->assertOk()
            ->assertSee(Storage::url('images/crop_300x300_lampada-1.jpg'), false);

        $this->get(route('article.show', $article))
            ->assertOk()
            ->assertSee(Storage::url('images/crop_300x300_lampada-1.jpg'), false)
            ->assertSee(Storage::url('images/crop_300x300_lampada-2.jpg'), false)
            ->assertSee('articleCarousel')
            ->assertDontSee('Nessuna foto inserita dall\'utente');
    }

    public function test_revisor_dashboard_shows_uploaded_images(): void
    {
        Storage::fake('public');
        $this->seed();

        $article = Article::factory()->pending()->create([
            'title' => 'Sedia da revisionare',
        ]);
        Image::factory()->for($article)->create(['path' => 'images/sedia.jpg']);

        $this->actingAs(User::factory()->revisor()->create())
            ->get(route('revisor.index'))
            ->assertOk()
            ->assertSee(Storage::url('images/crop_300x300_sedia.jpg'), false)
            ->assertDontSee('Foto segnaposto');
    }

    public function test_article_image_alt_escapes_the_title(): void
    {
        $this->seed();

        $article = Article::factory()->accepted()->create([
            'title' => '<script>alert("xss")</script>',
        ]);
        Image::factory()->for($article)->create(['path' => 'images/xss.jpg']);

        $this->get(route('article.show', $article))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
