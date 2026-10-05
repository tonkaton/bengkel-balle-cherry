<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * $title dideklarasikan eksplisit di sini supaya nilai yang dikirim
     * lewat <x-guest-layout title="..."> benar-benar sampai ke variabel
     * $title di dalam layouts/guest.blade.php. Tanpa ini, atribut "title"
     * cuma nyangkut di $attributes dan tidak pernah terbaca oleh
     * {{ $title ?? '...' }} di dalam view-nya.
     */
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
