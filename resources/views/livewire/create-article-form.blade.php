<div>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <h1 class="h3 mb-4">{{ __('ui.insertAnAd') }}</h1>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit="store">
                <div class="mb-3">
                    <label for="title" class="form-label">{{ __('ui.title') }}</label>
                    <input type="text" id="title"
                           class="form-control @error('title') is-invalid @enderror"
                           wire:model.blur="title">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">{{ __('ui.price') }}</label>
                    <input type="number" step="0.01" min="0" id="price"
                           class="form-control @error('price') is-invalid @enderror"
                           wire:model.blur="price">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">{{ __('ui.description') }}</label>
                    <textarea id="description" rows="5"
                              class="form-control @error('description') is-invalid @enderror"
                              wire:model.blur="description"></textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="form-label">{{ __('ui.category') }}</label>
                    <select id="category"
                            class="form-select @error('category') is-invalid @enderror"
                            wire:model.blur="category">
                        <option value="">{{ __('ui.selectCategory') }}</option>
                        @foreach ($categories as $item)
                            <option value="{{ $item->id }}">{{ __('ui.'.$item->name) }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="temporary_images" class="form-label">{{ __('ui.images') }}</label>
                    <input type="file" id="temporary_images" wire:model.live="temporary_images" multiple
                           class="form-control shadow @error('temporary_images.*') is-invalid @enderror">
                    @error('temporary_images.*')
                        <p class="fst-italic text-danger">{{ $message }}</p>
                    @enderror
                    @error('temporary_images')
                        <p class="fst-italic text-danger">{{ $message }}</p>
                    @enderror
                </div>

                @if (! empty($images))
                    <div class="row">
                        <div class="col-12">
                            <p>{{ __('ui.photoPreview') }}</p>
                            <div class="row border border-4 border-success rounded shadow py-4">
                                @foreach ($images as $key => $image)
                                    <div class="col d-flex flex-column align-items-center my-3" wire:key="preview-{{ $key }}">
                                        <div class="img-preview mx-auto shadow rounded"
                                             style="background-image: url({{ $image->temporaryUrl() }});"></div>
                                        <button type="button" class="btn mt-1 btn-danger"
                                                wire:click="removeImage({{ $key }})"
                                                aria-label="{{ __('ui.removeImage') }}">X</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn btn-presto mt-3">{{ __('ui.publishAd') }}</button>
            </form>
        </div>
    </div>
</div>
