<div>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <h1 class="h3 mb-4">Inserisci un annuncio</h1>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit="store">
                <div class="mb-3">
                    <label for="title" class="form-label">Titolo</label>
                    <input type="text" id="title"
                           class="form-control @error('title') is-invalid @enderror"
                           wire:model.blur="title">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Prezzo</label>
                    <input type="number" step="0.01" min="0" id="price"
                           class="form-control @error('price') is-invalid @enderror"
                           wire:model.blur="price">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descrizione</label>
                    <textarea id="description" rows="5"
                              class="form-control @error('description') is-invalid @enderror"
                              wire:model.blur="description"></textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="form-label">Categoria</label>
                    <select id="category"
                            class="form-select @error('category') is-invalid @enderror"
                            wire:model.blur="category">
                        <option value="">Seleziona una categoria</option>
                        @foreach ($categories as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-presto">Pubblica annuncio</button>
            </form>
        </div>
    </div>
</div>
