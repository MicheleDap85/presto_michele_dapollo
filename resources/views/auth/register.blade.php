<x-layout title="Registrati - PRESTO">
    <div class="container py-5">
        <div class="row justify-content-center height-custom align-items-center">
            <div class="col-12 col-md-6 col-lg-5">
                <h1 class="h3 mb-4 text-center">Crea il tuo account</h1>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Indirizzo email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Conferma la tua password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-presto w-100">Registrati</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Hai già un account? <a href="{{ route('login') }}">Login</a>
                </p>
            </div>
        </div>
    </div>
</x-layout>
