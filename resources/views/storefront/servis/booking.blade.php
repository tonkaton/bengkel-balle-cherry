<x-storefront-layout title="Booking {{ $servis->nama }}">
    <a href="{{ route('servis.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-700">&larr; Semua servis</a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_340px]">
        <div class="kartu p-6">
            <div class="mb-5 flex items-center gap-4">
                <img src="{{ $servis->gambar_url }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">Booking {{ $servis->nama }}</h1>
                    <p class="text-sm text-slate-500">{{ $servis->harga_format }} &middot; estimasi {{ $servis->estimasi_format }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('servis.booking.simpan', $servis) }}" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nama_pelanggan" value="Nama" />
                        <x-text-input id="nama_pelanggan" name="nama_pelanggan" class="mt-1 w-full" value="{{ old('nama_pelanggan') }}" required autofocus />
                        <x-input-error :messages="$errors->get('nama_pelanggan')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="no_hp" value="Nomor HP" />
                        <x-text-input id="no_hp" name="no_hp" class="mt-1 w-full" value="{{ old('no_hp') }}" placeholder="081234567890" required />
                        <x-input-error :messages="$errors->get('no_hp')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="kendaraan" value="Merek / Tipe Motor" />
                        <x-text-input id="kendaraan" name="kendaraan" class="mt-1 w-full" value="{{ old('kendaraan') }}" placeholder="Honda Vario 125" required />
                        <x-input-error :messages="$errors->get('kendaraan')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="plat_nomor" value="Plat Nomor" />
                        <x-text-input id="plat_nomor" name="plat_nomor" class="mt-1 w-full uppercase" value="{{ old('plat_nomor') }}" placeholder="B 1234 ABC" required />
                        <x-input-error :messages="$errors->get('plat_nomor')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="tanggal" value="Tanggal" />
                        <x-text-input id="tanggal" name="tanggal" type="date" class="mt-1 w-full" value="{{ old('tanggal') }}"
                            min="{{ now()->toDateString() }}" max="{{ now()->addDays(30)->toDateString() }}" required />
                        <x-input-error :messages="$errors->get('tanggal')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="jam" value="Jam" />
                        <select id="jam" name="jam" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">-- Pilih jam --</option>
                            @foreach ($jamLayanan as $jam)
                                <option value="{{ $jam }}" @selected(old('jam') === $jam)>{{ $jam }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('jam')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="keluhan" value="Keluhan (opsional)" />
                    <textarea id="keluhan" name="keluhan" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Contoh: rem depan bunyi, tarikan berat">{{ old('keluhan') }}</textarea>
                    <x-input-error :messages="$errors->get('keluhan')" class="mt-1" />
                </div>

                <x-primary-button type="submit" class="w-full justify-center sm:w-auto">Kirim Booking</x-primary-button>
            </form>
        </div>

        <aside>
            <div class="kartu p-4">
                <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <x-icon.sparkles class="h-5 w-5 text-brand-600" /> Biasanya Dibutuhkan Bersama
                </h2>
                <p class="mb-3 text-xs text-slate-500">Item yang paling sering diambil bersama {{ $servis->nama }}.</p>
                @if ($rekomendasi->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada data yang cukup untuk rekomendasi.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($rekomendasi as $item)
                            <x-storefront.rekomendasi-card :item="$item" />
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>
    </div>
</x-storefront-layout>
