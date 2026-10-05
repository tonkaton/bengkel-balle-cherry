@props(['label', 'value', 'icon' => null, 'accent' => 'brand'])
@php
$warna = [
    'brand' => 'bg-brand-50 text-brand-600',
    'emerald' => 'bg-emerald-50 text-emerald-600',
    'amber' => 'bg-amber-50 text-amber-600',
    'rose' => 'bg-rose-50 text-rose-600',
][$accent] ?? 'bg-brand-50 text-brand-600';
@endphp

<div class="kartu flex items-center gap-4 p-5">
    @if ($icon)
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $warna }}">
            <x-dynamic-component :component="'icon.'.$icon" class="h-6 w-6" />
        </div>
    @endif
    <div>
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <p class="text-xl font-semibold text-slate-900">{{ $value }}</p>
    </div>
</div>
