{{--
    Notifikasi kecil di bagian atas halaman untuk pesan sukses/galat yang
    dikirim controller lewat ->with('sukses', ...) atau ->with('galat', ...).
    Ditaruh satu kali di layout utama supaya setiap controller tidak perlu
    mengurus tampilan notifikasinya sendiri-sendiri.
--}}
@if (session('sukses'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
        class="mb-4 flex items-start justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <span>{{ session('sukses') }}</span>
        <button type="button" @click="show = false" class="shrink-0 text-emerald-500 hover:text-emerald-700">&times;</button>
    </div>
@endif

@if (session('galat'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-start justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <span>{{ session('galat') }}</span>
        <button type="button" @click="show = false" class="shrink-0 text-rose-500 hover:text-rose-700">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p class="font-medium">Ada masukan yang perlu diperbaiki:</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif
