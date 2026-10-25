<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransaksiSeeder extends Seeder
{
    /**
     * Dua belas transaksi contoh selama sebulan terakhir, supaya halaman
     * dashboard, analisis popularitas, moderasi rating, dan rekomendasi
     * langsung punya data untuk dicoba.
     *
     * Sebelas transaksi sudah diberi rating, transaksi terakhir sengaja
     * belum supaya alur memberi rating lewat tautan struk masih bisa dicoba.
     * Polanya diatur agar sebagian besar produk punya minimal 3 transaksi
     * berating (metode KNN), sedangkan Aki Kering baru 2 sehingga memakai
     * metode co-purchase - jadi kedua metode terlihat di aplikasi.
     *
     * Stok produk tidak dikurangi karena ini data riwayat contoh. Kode
     * transaksinya tetap (akhiran CNTH01-12), jadi seeder aman dijalankan
     * ulang tanpa membuat data ganda.
     */
    public function run(): void
    {
        $produk = Produk::pluck('id', 'barcode');
        $kampas = '8991234567890';
        $oli = '8991234567891';
        $busi = '8991234567892';
        $filter = '8991234567893';
        $aki = '8991234567894';

        // [hari yang lalu, jam, [barcode => jumlah], rating, komentar]
        $contoh = [
            [28, '09:15', [$oli => 2, $filter => 1], 5, 'Oli dan filter udaranya cocok, mesin terasa lebih halus.'],
            [26, '10:40', [$kampas => 1], 4, 'Pengereman jadi pakem lagi.'],
            [24, '13:05', [$busi => 4, $oli => 1], 5, 'Busi iridium bikin tarikan lebih enteng.'],
            [22, '15:30', [$aki => 1], 4, 'Aki awet, starter langsung menyala.'],
            [19, '08:50', [$oli => 1, $filter => 1, $busi => 4], 5, 'Paket servis lengkap, pelayanannya cepat.'],
            [16, '11:20', [$kampas => 1, $oli => 1], 3, 'Barang sesuai, tapi antreannya agak lama.'],
            [13, '14:10', [$filter => 1], 4, 'Filter udara original, pas dipasang.'],
            [10, '16:00', [$aki => 1, $busi => 4], 5, 'Masalah kelistrikan beres semua.'],
            [7, '09:45', [$oli => 2], 4, 'Harga olinya bersahabat.'],
            [5, '10:30', [$kampas => 1, $filter => 1], 4, 'Kampas rem dan filter udara tersedia lengkap.'],
            [3, '13:50', [$busi => 4], 5, 'Busi langsung dipasang di tempat, mantap.'],
            [1, '15:15', [$oli => 1, $kampas => 1, $aki => 1], null, null],
        ];

        DB::transaction(function () use ($contoh, $produk) {
            foreach ($contoh as $i => [$hariLalu, $jam, $isi, $rating, $komen]) {
                $tanggal = now()->subDays($hariLalu)->setTimeFromTimeString($jam);
                $akhiran = sprintf('CNTH%02d', $i + 1);

                $transaksi = Transaksi::where('kode_transaksi', 'like', '%'.$akhiran)->first();

                if (! $transaksi) {
                    $transaksi = $this->buatTransaksi($akhiran, $tanggal, $isi, $produk);
                }

                if ($rating && ! $transaksi->ratingKomen()->exists()) {
                    $transaksi->ratingKomen()->create([
                        'rating' => $rating,
                        'komen' => $komen,
                        'tanggal' => $transaksi->tanggal->copy()->addHours(3),
                    ]);
                }
            }
        });
    }

    private function buatTransaksi(string $akhiran, Carbon $tanggal, array $isi, Collection $produk): Transaksi
    {
        $transaksi = Transaksi::create([
            'kode_transaksi' => 'TRX'.$tanggal->format('Ymd').$akhiran,
            'total_harga' => 0,
            'tanggal' => $tanggal,
        ]);

        $total = 0;
        foreach ($isi as $barcode => $jumlah) {
            $item = Produk::find($produk[$barcode]);
            $transaksi->detailTransaksi()->create([
                'produk_id' => $item->id,
                'jumlah' => $jumlah,
                'harga_saat_beli' => $item->harga,
            ]);
            $total += $item->harga * $jumlah;
        }

        $transaksi->update(['total_harga' => $total]);

        return $transaksi;
    }
}
