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

class UserStorySevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_article_dispatches_async_google_vision_jobs_for_each_image(): void
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
            ->set('temporary_images', [
                UploadedFile::fake()->image('bici.jpg', 800, 600),
                UploadedFile::fake()->image('sella.png', 640, 480),
            ])
            ->call('store')
            ->assertHasNoErrors();

        $article = Article::query()->where('title', 'Bici da corsa')->firstOrFail();
        $imageIds = $article->images()->pluck('id')->all();

        $this->assertSame(2, count($imageIds));
        $this->assertNull($article->images()->first()->adult);
        $this->assertNull($article->images()->first()->labels);

        Queue::assertPushed(RemoveFaces::class, 2);

        foreach ($imageIds as $imageId) {
            Queue::assertPushedWithChain(
                RemoveFaces::class,
                [
                    ResizeImage::class,
                    GoogleVisionSafeSearch::class,
                    GoogleVisionLabelImage::class,
                ],
                fn (RemoveFaces $job): bool => $this->jobImageId($job) === $imageId
            );
        }
    }

    public function test_google_vision_jobs_are_not_dispatched_when_an_article_has_no_images(): void
    {
        Queue::fake([
            RemoveFaces::class,
            ResizeImage::class,
            GoogleVisionSafeSearch::class,
            GoogleVisionLabelImage::class,
        ]);
        $this->seed();

        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CreateArticleForm::class)
            ->set('title', 'Libro usato')
            ->set('description', 'Libro in buone condizioni, senza foto.')
            ->set('price', 12)
            ->set('category', $category->id)
            ->call('store')
            ->assertHasNoErrors();

        Queue::assertNotPushed(RemoveFaces::class);
        Queue::assertNotPushed(GoogleVisionSafeSearch::class);
        Queue::assertNotPushed(GoogleVisionLabelImage::class);
        Queue::assertNotPushed(ResizeImage::class);
    }

    public function test_safe_search_job_returns_when_the_image_does_not_exist(): void
    {
        (new GoogleVisionSafeSearch(999999))->handle();

        $this->assertSame(0, Image::query()->count());
    }

    public function test_label_image_job_returns_when_the_image_does_not_exist(): void
    {
        (new GoogleVisionLabelImage(999999))->handle();

        $this->assertSame(0, Image::query()->count());
    }

    public function test_image_labels_are_cast_to_an_array(): void
    {
        $article = Article::factory()->pending()->create();

        $image = Image::factory()->for($article)->create([
            'labels' => ['Lamp', 'Furniture'],
        ]);

        $this->assertSame(['Lamp', 'Furniture'], $image->fresh()->labels);
    }

    public function test_revisor_dashboard_shows_vision_labels_and_safe_search_ratings(): void
    {
        $this->seed();

        $article = Article::factory()->pending()->create([
            'title' => 'Sedia da revisionare',
        ]);
        Image::factory()->for($article)->create([
            'path' => 'articles/3/sedia.jpg',
            'labels' => ['Chair', 'Wood'],
            'adult' => 'text-success bi bi-check-circle-fill',
            'violence' => 'text-danger bi bi-dash-circle-fill',
            'spoof' => 'text-warning bi bi-exclamation-circle-fill',
            'racy' => 'text-secondary bi bi-circle-fill',
            'medical' => 'text-success bi bi-check-circle-fill',
        ]);

        $this->actingAs(User::factory()->revisor()->create())
            ->get(route('revisor.index'))
            ->assertOk()
            ->assertSee('Labels')
            ->assertSee('Ratings')
            ->assertSee('#Chair')
            ->assertSee('#Wood')
            ->assertSee('adult')
            ->assertSee('violence')
            ->assertSee('spoof')
            ->assertSee('racy')
            ->assertSee('medical')
            ->assertSee('text-success bi bi-check-circle-fill', false)
            ->assertSee('text-danger bi bi-dash-circle-fill', false)
            ->assertSee(Storage::url('articles/3/crop_300x300_sedia.jpg'), false)
            ->assertDontSee('No labels');
    }

    public function test_revisor_dashboard_shows_no_labels_when_vision_has_not_returned_any(): void
    {
        $this->seed();

        $article = Article::factory()->pending()->create([
            'title' => 'Tavolo senza etichette',
        ]);
        Image::factory()->for($article)->create([
            'path' => 'articles/4/tavolo.jpg',
            'labels' => null,
        ]);

        $this->actingAs(User::factory()->revisor()->create())
            ->get(route('revisor.index'))
            ->assertOk()
            ->assertSee('No labels');
    }

    public function test_revisor_vision_labels_and_title_are_escaped(): void
    {
        $this->seed();

        $article = Article::factory()->pending()->create([
            'title' => '<script>alert("xss")</script>',
        ]);
        Image::factory()->for($article)->create([
            'path' => 'articles/9/xss.jpg',
            'labels' => ['<img src=x onerror=alert(1)>'],
        ]);

        $this->actingAs(User::factory()->revisor()->create())
            ->get(route('revisor.index'))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_google_credential_file_is_gitignored(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));

        $this->assertNotFalse($gitignore);
        $this->assertStringContainsString('google_credential.json', $gitignore);
    }

    private function jobImageId(RemoveFaces $job): int
    {
        return (new \ReflectionProperty($job, 'article_image_id'))->getValue($job);
    }
}
