<x-layout title="{{ __('ui.workWithUs') }} - PRESTO">
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <h1 class="h3 mb-3">{{ __('ui.workWithUs') }}</h1>

                @if (Auth::user()->is_revisor)
                    <div class="alert alert-success">
                        {{ __('ui.alreadyRevisor') }}
                        <a href="{{ route('revisor.index') }}" class="alert-link">{{ __('ui.revisorArea') }}</a>.
                    </div>
                @else
                    <p class="mb-4">
                        {{ __('ui.becomeRevisorIntro') }}
                    </p>

                    <form action="{{ route('become.revisor.submit') }}" method="POST" class="card shadow-sm border-0">
                        @csrf
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">{{ __('ui.name') }}</label>
                                <input type="text" id="name" class="form-control" value="{{ Auth::user()->name }}" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">{{ __('ui.email') }}</label>
                                <input type="email" id="email" class="form-control" value="{{ Auth::user()->email }}" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="motivation" class="form-label">{{ __('ui.whyRevisor') }}</label>
                                <textarea
                                    name="motivation"
                                    id="motivation"
                                    class="form-control @error('motivation') is-invalid @enderror"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="{{ __('ui.motivationPlaceholder') }}"
                                >{{ old('motivation') }}</textarea>
                                @error('motivation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-presto">{{ __('ui.sendRequest') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-layout>
