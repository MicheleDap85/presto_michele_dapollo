@props(['article'])

<div class="card card-w mx-auto h-100 shadow-sm">
    <a href="{{ route('article.show', compact('article')) }}">
        <img src="{{ $article->images->isNotEmpty() ? $article->images->first()->getUrl(300, 300) : 'https://picsum.photos/seed/article-'.$article->id.'/300/300' }}"
             class="card-img-top"
             alt="{{ __('ui.articleImage', ['n' => 1, 'title' => $article->title]) }}">
    </a>
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">{{ $article->title }}</h5>
        <p class="card-text fw-semibold mb-2">{{ number_format($article->price, 2, ',', '.') }} €</p>
        <p class="mb-3">
            <a href="{{ route('article.byCategory', $article->category) }}" class="badge text-bg-light text-decoration-none border">
                {{ __('ui.'.$article->category->name) }}
            </a>
        </p>
        <a href="{{ route('article.show', compact('article')) }}" class="btn btn-presto mt-auto">
            {{ __('ui.seeDetail') }}
        </a>
    </div>
</div>
