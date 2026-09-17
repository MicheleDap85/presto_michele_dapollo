<x-layout title="PRESTO - Homepage">
    <section class="container py-5">
        <div class="hero-presto p-4 p-lg-5 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3">Dai una seconda vita a ciò che non usi più</h1>
                    <p class="fs-5 mb-4">
                        PRESTO è il portale per comprare e vendere oggetti usati in modo semplice e veloce.
                    </p>
                    <a href="{{ route('article.create') }}" class="btn btn-light btn-lg">
                        Inserisci annuncio
                    </a>
                </div>
            </div>
        </div>

        @isset($categories)
            <h2 class="h4 mb-3">Categorie</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($categories as $category)
                    <span class="category-pill">{{ $category->name }}</span>
                @endforeach
            </div>
        @endisset
    </section>
</x-layout>
