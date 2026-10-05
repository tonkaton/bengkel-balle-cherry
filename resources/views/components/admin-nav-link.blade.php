{{--
    Satu tautan menu di sidebar admin. Propnya cuma "active" (boolean) -
    pemanggilnya yang menentukan lewat request()->routeIs(...), supaya
    komponen ini tidak perlu tahu apa-apa soal routing.
--}}
@props(['active' => false])

<a {{ $attributes->merge([
    'class' => 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ' .
        ($active
            ? 'bg-brand-600 text-white shadow-sm'
            : 'text-slate-300 hover:bg-slate-800 hover:text-white'),
]) }}>
    {{ $slot }}
</a>
