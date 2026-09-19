<footer class="footer-presto py-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <p class="mb-0">{{ __('ui.copyright', ['year' => now()->year]) }}</p>

        <ul class="nav">
            <li class="nav-item">
                <a href="{{ route('homepage') }}" class="nav-link px-2 text-light">{{ __('ui.home') }}</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('article.index') }}" class="nav-link px-2 text-light">{{ __('ui.ads') }}</a>
            </li>
            @auth
                <li class="nav-item">
                    <a href="{{ route('article.create') }}" class="nav-link px-2 text-light">{{ __('ui.insertAd') }}</a>
                </li>
            @endauth
            <li class="nav-item">
                <a href="{{ route('become.revisor') }}" class="nav-link px-2 text-light">{{ __('ui.workWithUs') }}</a>
            </li>
        </ul>
    </div>
</footer>
