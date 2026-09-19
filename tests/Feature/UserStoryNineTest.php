<?php

namespace Tests\Feature;

use App\Jobs\ResizeImage;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class UserStoryNineTest extends TestCase
{
    public function test_watermark_image_is_stored_in_resources(): void
    {
        $this->assertFileExists(base_path('resources/img/watermark.png'));
    }

    #[RequiresPhpExtension('gd')]
    public function test_resize_image_job_applies_a_visible_watermark_on_the_cropped_image(): void
    {
        $folder = 'articles/us9-'.uniqid();
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

            $topLeft = $this->jpegRgb($cropPath, 10, 10);
            $watermarked = $this->jpegRgb($cropPath, 260, 260);

            $this->assertGreaterThan(150, $topLeft[0]);
            $this->assertLessThan(80, $topLeft[1]);
            $this->assertNotSame($topLeft, $watermarked);
            $this->assertGreaterThan(40, $this->colorDistance($topLeft, $watermarked));
        } finally {
            File::deleteDirectory(storage_path('app/public/'.$folder));
        }
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function jpegRgb(string $path, int $x, int $y): array
    {
        $image = imagecreatefromjpeg($path);
        $this->assertNotFalse($image);
        $pixel = imagecolorat($image, $x, $y);
        imagedestroy($image);

        return [($pixel >> 16) & 0xFF, ($pixel >> 8) & 0xFF, $pixel & 0xFF];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $first
     * @param  array{0: int, 1: int, 2: int}  $second
     */
    private function colorDistance(array $first, array $second): float
    {
        return hypot($first[0] - $second[0], hypot($first[1] - $second[1], $first[2] - $second[2]));
    }
}
