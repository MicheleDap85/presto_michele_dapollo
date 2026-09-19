<nav class="navbar navbar-expand-lg navbar-dark navbar-presto">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('homepage') }}">PRESTO</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('homepage') ? 'active' : '' }}"
                       href="{{ route('homepage') }}">{{ __('ui.home') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('article.index') ? 'active' : '' }}"
                       href="{{ route('article.index') }}">{{ __('ui.allArticles') }}</a>
                </li>

                @isset($categories)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('article.byCategory') ? 'active' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('ui.categories') }}
                        </a>
                        <ul class="dropdown-menu">
                            @forelse ($categories as $category)
                                <li>
                                    <a class="dropdown-item" href="{{ route('article.byCategory', compact('category')) }}">
                                        {{ __('ui.'.$category->name) }}
                                    </a>
                                </li>
                                @if (! $loop->last)
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                            @empty
                                <li><span class="dropdown-item-text">{{ __('ui.noCategories') }}</span></li>
                            @endforelse
                        </ul>
                    </li>
                @endisset
            </ul>

            <form class="d-flex ms-auto my-2 my-lg-0 me-lg-3" role="search" action="{{ route('article.search') }}" method="GET">
                <div class="input-group">
                    <input type="search" name="query" class="form-control" placeholder="{{ __('ui.search') }}" aria-label="{{ __('ui.search') }}"
                           value="{{ request('query') }}">
                    <button type="submit" class="input-group-text btn btn-outline-success">
                        {{ __('ui.search') }}
                    </button>
                </div>
            </form>

            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <li class="nav-item d-flex align-items-center gap-1 me-lg-2">
                    <x-_locale lang="it" />
                    <x-_locale lang="uk" />
                    <x-_locale lang="es" />
                </li>
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('ui.hello') }}, {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if (Auth::user()->is_revisor)
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center gap-3"
                                       href="{{ route('revisor.index') }}">
                                        {{ __('ui.revisorArea') }}
                                        @php($toRevise = \App\Models\Article::toBeRevisedCount())
                                        @if ($toRevise > 0)
                                            <span class="badge rounded-pill bg-danger">{{ $toRevise }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('article.create') }}">
                                    {{ __('ui.insertAd') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('form-logout').submit();"
                                   class="dropdown-item">{{ __('ui.logout') }}</a>
                                <form id="form-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('ui.helloGuest') }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('login') }}">{{ __('ui.login') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('register') }}">{{ __('ui.register') }}</a></li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
