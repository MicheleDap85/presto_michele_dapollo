<footer class="footer-presto py-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <p class="mb-0">© {{ now()->year }} PRESTO — compra e vendi in un attimo</p>

        <ul class="nav">
            <li class="nav-item">
                <a href="{{ route('homepage') }}" class="nav-link px-2 text-light">Home</a>
            </li>
            @auth
                <li class="nav-item">
                    <a href="{{ route('article.create') }}" class="nav-link px-2 text-light">Inserisci annuncio</a>
                </li>
            @endauth
        </ul>
    </div>
</footer>
