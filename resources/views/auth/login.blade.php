<x-layout title="Login - PRESTO">
    <div class="container py-5">
        <div class="row justify-content-center height-custom align-items-center">
            <div class="col-12 col-md-6 col-lg-4">
                <h1 class="h3 mb-4 text-center">Accedi a PRESTO</h1>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Indirizzo email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required autofocus>
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

                    <button type="submit" class="btn btn-presto w-100">Login</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Non hai un account? <a href="{{ route('register') }}">Registrati</a>
                </p>
            </div>
        </div>
    </div>
</x-layout>
