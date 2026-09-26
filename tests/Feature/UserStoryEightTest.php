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
use Google\Cloud\Vision\V1\BoundingPoly;
use Google\Cloud\Vision\V1\FaceAnnotation;
use Google\Cloud\Vision\V1\Vertex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class UserStoryEightTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_article_dispatches_remove_faces_chained_with_resize_and_vision_jobs(): void
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

    public function test_remove_faces_is_not_dispatched_when_an_article_has_no_images(): void
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
        Queue::assertNotPushed(ResizeImage::class);
        Queue::assertNotPushed(GoogleVisionSafeSearch::class);
        Queue::assertNotPushed(GoogleVisionLabelImage::class);
    }

    public function test_remove_faces_job_returns_when_the_image_does_not_exist(): void
    {
        (new RemoveFaces(999999))->handle();

        $this->assertSame(0, Image::query()->count());
    }

    public function test_remove_faces_skips_google_when_credentials_are_missing(): void
    {
        if (is_file(base_path('google_credential.json'))) {
            $this->markTestSkipped('Google Vision credentials are present.');
        }

        $folder = 'articles/us8-'.uniqid();
        $relativePath = $folder.'/photo.jpg';
        $sourcePath = storage_path('app/public/'.$relativePath);

        File::ensureDirectoryExists(dirname($sourcePath));
        file_put_contents($sourcePath, 'photo');

        $image = Image::factory()->create(['path' => $relativePath]);

        try {
            (new RemoveFaces($image->id))->handle();

            $this->assertFileExists($sourcePath);
            $this->assertSame('photo', file_get_contents($sourcePath));
        } finally {
            File::deleteDirectory(storage_path('app/public/'.$folder));
        }
    }

    public function test_censor_image_is_stored_in_resources(): void
    {
        $this->assertFileExists(base_path('resources/img/face.png'));
    }

    #[RequiresPhpExtension('gd')]
    public function test_detected_faces_are_covered_with_the_censor_image(): void
    {
        $folder = 'articles/us8-'.uniqid();
        $relativePath = $folder.'/photo.jpg';
        $sourcePath = storage_path('app/public/'.$relativePath);

        File::ensureDirectoryExists(dirname($sourcePath));

        $canvas = imagecreatetruecolor(400, 400);
        $red = imagecolorallocate($canvas, 220, 40, 40);
        imagefilledrectangle($canvas, 0, 0, 399, 399, $red);
        imagejpeg($canvas, $sourcePath);
        imagedestroy($canvas);

        $originalPixel = $this->jpegPixel($sourcePath, 100, 100);

        try {
            (new RemoveFaces(1))->applyFaceCensor($sourcePath, [$this->faceAnnotation(50, 50, 150, 150)]);

            $this->assertFileExists($sourcePath);
            $this->assertNotSame($originalPixel, $this->jpegPixel($sourcePath, 100, 100));
        } finally {
            File::deleteDirectory(storage_path('app/public/'.$folder));
        }
    }

    #[RequiresPhpExtension('gd')]
    public function test_images_without_faces_are_left_unchanged(): void
    {
        $folder = 'articles/us8-'.uniqid();
        $relativePath = $folder.'/photo.jpg';
        $sourcePath = storage_path('app/public/'.$relativePath);

        File::ensureDirectoryExists(dirname($sourcePath));

        $canvas = imagecreatetruecolor(200, 200);
        $blue = imagecolorallocate($canvas, 40, 80, 220);
        imagefilledrectangle($canvas, 0, 0, 199, 199, $blue);
        imagejpeg($canvas, $sourcePath);
        imagedestroy($canvas);

        $original = file_get_contents($sourcePath);

        try {
            (new RemoveFaces(1))->applyFaceCensor($sourcePath, []);

            $this->assertSame($original, file_get_contents($sourcePath));
        } finally {
            File::deleteDirectory(storage_path('app/public/'.$folder));
        }
    }

    private function faceAnnotation(int $left, int $top, int $right, int $bottom): FaceAnnotation
    {
        $poly = new BoundingPoly;
        $poly->setVertices([
            (new Vertex)->setX($left)->setY($top),
            (new Vertex)->setX($right)->setY($top),
            (new Vertex)->setX($right)->setY($bottom),
            (new Vertex)->setX($left)->setY($bottom),
        ]);

        $face = new FaceAnnotation;
        $face->setBoundingPoly($poly);

        return $face;
    }

    private function jpegPixel(string $path, int $x, int $y): int
    {
        $image = imagecreatefromjpeg($path);
        $this->assertNotFalse($image);
        $pixel = imagecolorat($image, $x, $y);
        imagedestroy($image);

        return $pixel;
    }

    private function jobImageId(RemoveFaces $job): int
    {
        return (new \ReflectionProperty($job, 'article_image_id'))->getValue($job);
    }
}
