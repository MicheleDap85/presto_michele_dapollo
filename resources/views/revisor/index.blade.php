<x-layout title="{{ __('ui.revisorArea') }} - PRESTO">
    <section class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ __('ui.revisorArea') }}</h1>
                <p class="text-muted mb-0">{{ __('ui.revisorSubtitle') }}</p>
            </div>

            @if (session('last_reviewed_article_id'))
                <form action="{{ route('revisor.undo') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary">
                        {{ __('ui.undoLast') }}
                    </button>
                </form>
            @endif
        </div>

        @if ($article_to_check)
            <div class="row g-4">
                <div class="col-lg-5">
                    <p class="mb-2">
                        <span class="badge text-bg-light border">{{ __('ui.'.$article_to_check->category->name) }}</span>
                    </p>
                    <h2 class="h4">{{ $article_to_check->title }}</h2>
                    <p class="fs-4 fw-semibold text-success mb-2">
                        {{ number_format($article_to_check->price, 2, ',', '.') }} €
                    </p>
                    <p class="text-muted">
                        {{ __('ui.publishedBy', ['name' => $article_to_check->user->name, 'date' => $article_to_check->created_at->format('d/m/Y')]) }}
                    </p>
                    <hr>
                    <h3 class="h6">{{ __('ui.description') }}</h3>
                    <p>{{ $article_to_check->description }}</p>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <form action="{{ route('revisor.accept', ['article' => $article_to_check]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">{{ __('ui.acceptAd') }}</button>
                        </form>

                        <form action="{{ route('revisor.reject', ['article' => $article_to_check]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-danger">{{ __('ui.rejectAd') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                @if ($article_to_check->images->count())
                    @foreach ($article_to_check->images as $key => $image)
                        <div class="col-12 col-lg-6">
                            <div class="card mb-3">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="{{ $image->getUrl(300, 300) }}"
                                             class="img-fluid rounded-start revisor-placeholder"
                                             alt="Immagine {{ $key + 1 }} dell'articolo '{{ $article_to_check->title }}'">
                                    </div>
                                    <div class="col-md-5 ps-3">
                                        <div class="card-body">
                                            <h5>Labels</h5>
                                            @if ($image->labels)
                                                @foreach ($image->labels as $label)
                                                    #{{ $label }},
                                                @endforeach
                                            @else
                                                <p class="fst-italic">No labels</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card-body">
                                            <h5>Ratings</h5>
                                            <div class="row justify-content-center vision-rating">
                                                <div class="col-2">
                                                    <div class="text-center mx-auto {{ $image->adult }}"></div>
                                                </div>
                                                <div class="col-10">adult</div>
                                            </div>
                                            <div class="row justify-content-center vision-rating">
                                                <div class="col-2">
                                                    <div class="text-center mx-auto {{ $image->violence }}"></div>
                                                </div>
                                                <div class="col-10">violence</div>
                                            </div>
                                            <div class="row justify-content-center vision-rating">
                                                <div class="col-2">
                                                    <div class="text-center mx-auto {{ $image->spoof }}"></div>
                                                </div>
                                                <div class="col-10">spoof</div>
                                            </div>
                                            <div class="row justify-content-center vision-rating">
                                                <div class="col-2">
                                                    <div class="text-center mx-auto {{ $image->racy }}"></div>
                                                </div>
                                                <div class="col-10">racy</div>
                                            </div>
                                            <div class="row justify-content-center vision-rating">
                                                <div class="col-2">
                                                    <div class="text-center mx-auto {{ $image->medical }}"></div>
                                                </div>
                                                <div class="col-10">medical</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    @for ($i = 0; $i < 6; $i++)
                        <div class="col-6 col-md-4 mb-4 text-center">
                            <img src="https://picsum.photos/seed/revisor-{{ $article_to_check->id }}-{{ $i }}/400/300"
                                 class="img-fluid rounded shadow revisor-placeholder"
                                 alt="{{ __('ui.placeholderPhotoShort', ['n' => $i + 1]) }}">
                        </div>
                    @endfor
                @endif
            </div>
        @else
            <div class="text-center py-5">
                <h2 class="h4 mb-3">{{ __('ui.noArticlesToRevise') }}</h2>
                <p class="text-muted mb-4">{{ __('ui.noPendingAds') }}</p>
                <a href="{{ route('homepage') }}" class="btn btn-presto">{{ __('ui.backToHomepage') }}</a>
            </div>
        @endif
    </section>
</x-layout>
