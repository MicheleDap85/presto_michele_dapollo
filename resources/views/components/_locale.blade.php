@props(['lang'])

<form action="{{ route('setLocale', $lang) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn locale-flag p-1 {{ app()->getLocale() === $lang ? 'is-active' : '' }}"
            aria-label="{{ __('ui.language_'.$lang) }}">
        <img src="{{ asset('vendor/blade-flags/country-'.$lang.'.svg') }}" width="32" height="32"
             alt="{{ __('ui.language_'.$lang) }}">
    </button>
</form>
