<x-layout title="{{ $article->title }} - PRESTO">
    <section class="container py-5">
        <div class="row g-4">
            <div class="col-lg-7">
                @if ($article->images->count() > 0)
                    <div id="articleCarousel" class="carousel slide shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            @foreach ($article->images as $key => $image)
                                <div class="carousel-item @if ($loop->first) active @endif">
                                    <img src="{{ $image->getUrl(300, 300) }}"
                                         class="d-block w-100 article-carousel-image"
                                         alt="{{ __('ui.articleImage', ['n' => $key + 1, 'title' => $article->title]) }}">
                                </div>
                            @endforeach
                        </div>
                        @if ($article->images->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#articleCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">{{ __('ui.previous') }}</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#articleCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">{{ __('ui.next') }}</span>
                            </button>
                        @endif
                    </div>
                @else
                    <img src="https://picsum.photos/seed/article-{{ $article->id }}/900/560"
                         class="d-block w-100 rounded shadow article-carousel-image"
                         alt="{{ __('ui.noUserPhoto') }}">
                @endif
            </div>

            <div class="col-lg-5">
                <p class="mb-2">
                    <a href="{{ route('article.byCategory', $article->category) }}" class="badge text-bg-light text-decoration-none border">
                        {{ __('ui.'.$article->category->name) }}
                    </a>
                </p>
                <h1 class="h3">{{ $article->title }}</h1>
                <p class="display-6 fw-semibold text-success">{{ number_format($article->price, 2, ',', '.') }} €</p>
                <p class="text-muted">{{ __('ui.publishedBy', ['name' => $article->user->name, 'date' => $article->created_at->format('d/m/Y')]) }}</p>
                <hr>
                <h2 class="h5">{{ __('ui.description') }}</h2>
                <p>{{ $article->description }}</p>
                <a href="{{ route('article.index') }}" class="btn btn-outline-secondary">{{ __('ui.backToAds') }}</a>
            </div>
        </div>
    </section>
</x-layout>
