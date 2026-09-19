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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class UserStorySixTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_article_dispatches_an_async_resize_job_for_each_image(): void
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

        $this->assertSame(2, $article->images()->count());

        foreach ($article->images as $image) {
            $this->assertStringStartsWith("articles/{$article->id}/", $image->path);
            Storage::disk('public')->assertExists($image->path);
            Storage::disk('public')->assertMissing(
                dirname($image->path).'/crop_300x300_'.basename($image->path)
            );
        }

        Queue::assertPushed(RemoveFaces::class, 2);
        Queue::assertPushedWithChain(RemoveFaces::class, [
            ResizeImage::class,
            GoogleVisionSafeSearch::class,
            GoogleVisionLabelImage::class,
        ]);
    }

    #[RequiresPhpExtension('gd')]
    public function test_resize_image_job_writes_a_centered_300x300_crop(): void
    {
        $folder = 'articles/us6-'.uniqid();
        $relativePath = $folder.'/photo.jpg';
        $sourcePath = storage_path('app/public/'.$relativePath);
        $cropPath = storage_path('app/public/'.$folder.'/crop_300x300_photo.jpg');

        File::ensureDirectoryExists(dirname($sourcePath));

        $canvas = imagecreatetruecolor(800, 400);
        $red = imagecolorallocate($canvas, 220, 40, 40);
        imagefilledrectangle($canvas, 0, 0, 799, 399, $red);
        imagejpeg($canvas, $sourcePath);
        imagedestroy($canvas);

        try {
            (new ResizeImage($relativePath, 300, 300))->handle();

            $this->assertFileExists($cropPath);

            [$width, $height] = getimagesize($cropPath);

            $this->assertSame(300, $width);
            $this->assertSame(300, $height);
        } finally {
            File::deleteDirectory(storage_path('app/public/'.$folder));
        }
    }

    public function test_cropped_image_url_uses_the_300x300_filename(): void
    {
        $image = Image::factory()->make([
            'path' => 'articles/12/lampada.jpg',
        ]);

        $this->assertSame(Storage::url('articles/12/crop_300x300_lampada.jpg'), $image->getUrl(300, 300));
        $this->assertSame(Storage::url('articles/12/lampada.jpg'), $image->getUrl());
    }

    public function test_article_card_and_detail_use_the_cropped_image_url(): void
    {
        $this->seed();

        $article = Article::factory()->accepted()->create([
            'title' => 'Lampada vintage',
        ]);
        Image::factory()->for($article)->create(['path' => 'articles/1/lampada-1.jpg']);
        Image::factory()->for($article)->create(['path' => 'articles/1/lampada-2.jpg']);

        $this->get(route('article.index'))
            ->assertOk()
            ->assertSee(Storage::url('articles/1/crop_300x300_lampada-1.jpg'), false)
            ->assertDontSee(Storage::url('articles/1/lampada-1.jpg'), false);

        $this->get(route('article.show', $article))
            ->assertOk()
            ->assertSee(Storage::url('articles/1/crop_300x300_lampada-1.jpg'), false)
            ->assertSee(Storage::url('articles/1/crop_300x300_lampada-2.jpg'), false)
            ->assertDontSee(Storage::url('articles/1/lampada-1.jpg'), false);
    }

    public function test_revisor_dashboard_uses_the_cropped_image_url(): void
    {
        $this->seed();

        $article = Article::factory()->pending()->create([
            'title' => 'Sedia da revisionare',
        ]);
        Image::factory()->for($article)->create(['path' => 'articles/3/sedia.jpg']);

        $this->actingAs(User::factory()->revisor()->create())
            ->get(route('revisor.index'))
            ->assertOk()
            ->assertSee(Storage::url('articles/3/crop_300x300_sedia.jpg'), false)
            ->assertDontSee(Storage::url('articles/3/sedia.jpg'), false);
    }

    public function test_article_image_alt_escapes_the_title_on_cropped_images(): void
    {
        $this->seed();

        $article = Article::factory()->accepted()->create([
            'title' => '<script>alert("xss")</script>',
        ]);
        Image::factory()->for($article)->create(['path' => 'articles/9/xss.jpg']);

        $this->get(route('article.show', $article))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
