<?php

namespace App\Livewire;

use App\Jobs\GoogleVisionLabelImage;
use App\Jobs\GoogleVisionSafeSearch;
use App\Jobs\RemoveFaces;
use App\Jobs\ResizeImage;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\WithFileUploads;

class CreateArticleForm extends Component
{
    use WithFileUploads;

    #[Validate('required|min:5')]
    public $title = '';

    #[Validate('required|min:10')]
    public $description = '';

    #[Validate('required|numeric')]
    public $price = '';

    #[Validate('required')]
    public $category = '';

    public $article;

    public $images = [];

    public $temporary_images = [];

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('ui.titleRequired'),
            'title.min' => __('ui.titleMin'),
            'description.required' => __('ui.descriptionRequired'),
            'description.min' => __('ui.descriptionMin'),
            'price.required' => __('ui.priceRequired'),
            'price.numeric' => __('ui.priceNumeric'),
            'category.required' => __('ui.categoryRequired'),
            'temporary_images.max' => __('ui.imagesMax'),
            'temporary_images.*.image' => __('ui.imageType'),
            'temporary_images.*.max' => __('ui.imageMax'),
        ];
    }

    public function updatedTemporaryImages(): void
    {
        if ($this->validate([
            'temporary_images.*' => 'image|max:1024',
            'temporary_images' => 'max:6',
        ])) {
            $images = is_array($this->images) ? $this->images : [];

            foreach ($this->temporary_images as $image) {
                $images[] = $image;
            }

            $this->images = $images;
        }
    }

    public function removeImage(int|string $key): void
    {
        if (in_array($key, array_keys($this->images))) {
            $images = $this->images;
            unset($images[$key]);
            $this->images = $images;
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->article = Article::create([
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category,
            'user_id' => Auth::id(),
        ]);

        $uploadedImages = $this->uploadedImages();

        if ($uploadedImages !== []) {
            foreach ($uploadedImages as $image) {
                $newFileName = "articles/{$this->article->id}";
                $newImage = $this->article->images()->create(['path' => $image->store($newFileName, 'public')]);
                RemoveFaces::withChain([
                    new ResizeImage($newImage->path, 300, 300),
                    new GoogleVisionSafeSearch($newImage->id),
                    new GoogleVisionLabelImage($newImage->id),
                ])->dispatch($newImage->id);
            }

            FileUploadConfiguration::storage()->deleteDirectory(FileUploadConfiguration::directory());
        }

        session()->flash('success', __('ui.articleCreated'));
        $this->cleanForm();
    }

    /**
     * @return list<UploadedFile>
     */
    protected function uploadedImages(): array
    {
        $previewImages = $this->filesFrom($this->images);

        if ($previewImages !== []) {
            return $previewImages;
        }

        return $this->filesFrom($this->temporary_images);
    }

    /**
     * @return list<UploadedFile>
     */
    protected function filesFrom(mixed $group): array
    {
        if (! is_array($group)) {
            return [];
        }

        $files = [];

        foreach ($group as $image) {
            if ($image instanceof UploadedFile) {
                $files[] = $image;
            }
        }

        return $files;
    }

    protected function cleanForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->category = '';
        $this->price = '';
        $this->images = [];
        $this->temporary_images = [];
    }

    public function render()
    {
        return view('livewire.create-article-form', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
