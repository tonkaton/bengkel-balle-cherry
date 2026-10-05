{{--
    Form rating & komentar. Diakses lewat URL bertoken (rating_token), bukan
    ID transaksi biasa - lihat catatan keamanan lengkap di RatingController
    soal kenapa ini penting (perbaikan temuan F11 pada laporan audit).
--}}
<x-storefront-layout title="Beri Rating">
    <div class="mx-auto max-w-md">
        <div class="kartu p-6">
            <h1 class="text-lg font-semibold text-slate-900">Bagaimana pengalaman belanja Anda?</h1>
            <p class="mt-1 text-sm text-slate-500">Transaksi {{ $transaksi->kode_transaksi }}</p>

            <form method="POST" action="{{ route('rating.store', $transaksi->rating_token) }}" class="mt-6" x-data="{ rating: 0, hover: 0 }">
                @csrf

                <div class="flex justify-center gap-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0" class="p-1">
                            <x-icon.star solid class="h-9 w-9 transition"
                                x-bind:class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-slate-200'" />
                        </button>
                    @endfor
                </div>
                <input type="hidden" name="rating" x-model="rating">
                <x-input-error :messages="$errors->get('rating')" class="mt-2 text-center" />

                <div class="mt-6">
                    <x-input-label for="komen" value="Komentar (opsional)" />
                    <textarea id="komen" name="komen" rows="4"
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ceritakan pengalaman belanja Anda...">{{ old('komen') }}</textarea>
                    <x-input-error :messages="$errors->get('komen')" class="mt-2" />
                </div>

                <x-primary-button type="submit" class="mt-6 w-full" x-bind:disabled="rating === 0">
                    Kirim Rating
                </x-primary-button>
            </form>
        </div>
    </div>
</x-storefront-layout>
