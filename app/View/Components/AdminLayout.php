<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    /** Lihat catatan di GuestLayout.php soal kenapa $title dideklarasikan eksplisit. */
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        return view('layouts.admin');
    }
}
