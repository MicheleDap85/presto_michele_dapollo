<div>
    <h1>Un utente ha chiesto di lavorare con noi</h1>
    <h2>Ecco i suoi dati:</h2>
    <p>Nome: {{ $user->name }}</p>
    <p>Email: {{ $user->email }}</p>
    @if ($motivation)
        <p>Motivazione: {{ $motivation }}</p>
    @endif
    <p>
        <a href="{{ route('make.revisor', compact('user')) }}">Rendi revisore</a>
    </p>
</div>
