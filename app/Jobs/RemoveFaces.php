<?php

namespace App\Jobs;

use App\Models\Image;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\FaceAnnotation;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image as VisionImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image as SpatieImage;

class RemoveFaces implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private int $article_image_id;

    public function __construct(int $article_image_id)
    {
        $this->article_image_id = $article_image_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $i = Image::find($this->article_image_id);
        if (! $i) {
            return;
        }

        $src = storage_path('app/public/'.$i->path);
        if (! is_file($src)) {
            return;
        }

        $credentials = base_path('google_credential.json');
        if (! is_file($credentials)) {
            return;
        }

        $image = file_get_contents($src);
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$credentials);

        $googleVisionClient = new ImageAnnotatorClient;
        $google_image = new VisionImage([
            'content' => $image]);

        $googleFeature = new Feature;
        $googleFeature->setType(Type::FACE_DETECTION);

        $request = new AnnotateImageRequest;
        $request->setImage($google_image);
        $request->setFeatures([$googleFeature]);

        $batchRequest = new BatchAnnotateImagesRequest;
        $batchRequest->setRequests([$request]);

        $responseBatch = $googleVisionClient->batchAnnotateImages($batchRequest);
        $response = $responseBatch->getResponses()[0];
        $faces = $response->getFaceAnnotations();

        $this->applyFaceCensor($src, $faces);

        $googleVisionClient->close();
    }

    /**
     * Overlay the censorship image on each detected face.
     *
     * @param  iterable<int, FaceAnnotation>  $faces
     */
    public function applyFaceCensor(string $src, iterable $faces): void
    {
        $image = null;

        foreach ($faces as $face) {
            $boundingPoly = $face->getBoundingPoly();
            if ($boundingPoly === null) {
                continue;
            }

            $vertices = $boundingPoly->getVertices();
            $bounds = [];
            foreach ($vertices as $vertex) {
                $bounds[] = [$vertex->getX(), $vertex->getY()];
            }

            if (count($bounds) < 3) {
                continue;
            }

            $w = $bounds[2][0] - $bounds[0][0];
            $h = $bounds[2][1] - $bounds[0][1];

            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $image ??= SpatieImage::useImageDriver(ImageDriver::Gd)->loadFile($src);
            $image->watermark(
                base_path('resources/img/face.png'),
                AlignPosition::TopLeft,
                paddingX: $bounds[0][0],
                paddingY: $bounds[0][1],
                width: $w,
                height: $h,
                fit: Fit::Stretch
            );
        }

        if ($image !== null) {
            $image->save($src);
        }
    }
}
