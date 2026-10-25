<?php

namespace App\Services;

use App\Models\LogRekomendasi;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RekomendasiService - satu-satunya "otak" rekomendasi produk di aplikasi
 * ini. Sengaja disatukan di sini karena versi PHP lama (bengkel_knn2) punya
 * TIGA mekanisme berbeda yang semuanya diberi label "KNN", padahal cuma satu
 * yang benar-benar KNN (lihat Laporan Audit Teknis temuan F05 dan F08).
 * Supaya kesalahan itu tidak terulang, di sini setiap metode punya nama dan
 * dokumentasi yang jujur soal metode apa yang sebenarnya dipakai.
 *
 * Ada dua strategi rekomendasi:
 *
 * 1. Collaborative filtering (K-Nearest Neighbor asli) - dipakai kalau data
 *    rating sudah cukup banyak. Menghitung jarak Euclidean antar transaksi
 *    berdasarkan pola rating, lalu melihat produk apa saja yang dibeli oleh
 *    transaksi-transaksi "tetangga" yang paling mirip.
 * 2. Co-purchase (produk yang sering dibeli bersamaan) - dipakai sebagai
 *    cadangan kalau data rating belum cukup untuk KNN yang bisa diandalkan.
 *    Ini BUKAN K-Nearest Neighbor, jadi selalu diberi label "co_purchase",
 *    bukan "knn", di kolom metode pada tabel log_rekomendasi.
 *
 * Prinsip paling penting di seluruh service ini: skor yang dikembalikan
 * SELALU hasil hitungan asli dari data yang ada. Kalau datanya tidak cukup,
 * yang dikembalikan adalah koleksi kosong - bukan angka acak, bukan angka
 * bawaan 95 seperti temuan F05/F07 di laporan audit.
 */
class RekomendasiService
{
    /**
     * Berapa banyak transaksi "tetangga" (K) yang dipakai saat menghitung
     * collaborative filtering. Angka ini sama seperti default K=3 pada
     * fungsi rekomendasiKNN() versi PHP lama, supaya hasilnya bisa
     * dibandingkan langsung dengan yang dibahas di laporan skripsi.
     */
    private const K_TETANGGA = 3;

    /**
     * Minimal berapa transaksi lain yang harus punya rating SEBELUM kita
     * mau mempercayai hasil collaborative filtering. Kalau datanya kurang
     * dari ini, hasil similarity-nya terlalu tidak stabil untuk dipakai,
     * jadi lebih baik jujur pindah ke metode co-purchase.
     */
    private const MINIMAL_TRANSAKSI_PEMBANDING = 3;

    /**
     * Titik masuk utama: cari produk lain yang layak direkomendasikan
     * berdampingan dengan satu produk tertentu (dipakai di halaman produk
     * dan saat produk ditambahkan ke keranjang).
     *
     * @return Collection<int, array{produk: Produk, skor: float, metode: string}>
     */
    public function rekomendasikanUntukProduk(int $produkId, int $limit = 3): Collection
    {
        $hasilKnn = $this->collaborativeFiltering($produkId, $limit);

        if ($hasilKnn->isNotEmpty()) {
            return $hasilKnn;
        }

        return $this->coPurchase($produkId, $limit);
    }

    /**
     * Versi untuk keranjang belanja: gabungkan rekomendasi dari semua
     * produk yang sedang ada di keranjang, lalu urutkan berdasarkan skor
     * gabungan tertinggi. Produk yang sudah ada di keranjang tidak akan
     * direkomendasikan lagi (percuma menyarankan barang yang sudah dibeli).
     *
     * @param  list<int>  $produkIdDiKeranjang
     * @return Collection<int, array{produk: Produk, skor: float, metode: string}>
     */
    public function rekomendasikanUntukKeranjang(array $produkIdDiKeranjang, int $limit = 3): Collection
    {
        if (empty($produkIdDiKeranjang)) {
            return collect();
        }

        // Array biasa, bukan Collection: elemen Collection tidak bisa diubah
        // langsung lewat $gabungan[$kunci]['skor'] += ... (PHP menolaknya).
        $gabungan = [];

        foreach ($produkIdDiKeranjang as $produkId) {
            foreach ($this->rekomendasikanUntukProduk($produkId, $limit) as $item) {
                if (in_array($item['produk']->id, $produkIdDiKeranjang, true)) {
                    continue;
                }

                $kunci = $item['produk']->id;
                if (isset($gabungan[$kunci])) {
                    // Kalau produk yang sama muncul sebagai rekomendasi dari
                    // lebih dari satu produk di keranjang, skornya
                    // dijumlahkan - masuk akal karena berarti produk itu
                    // relevan dari beberapa sisi sekaligus.
                    $gabungan[$kunci]['skor'] += $item['skor'];
                } else {
                    $gabungan[$kunci] = $item;
                }
            }
        }

        return collect($gabungan)->sortByDesc('skor')->take($limit)->values();
    }

    /**
     * METODE 1: Collaborative filtering pakai jarak Euclidean - ini yang
     * secara teknis benar-benar layak disebut K-Nearest Neighbor.
     *
     * Karena aplikasi ini tidak punya akun pelanggan (checkout bersifat
     * anonim, seperti kasir toko biasa), maka "pengguna" yang dipakai untuk
     * KNN adalah TRANSAKSI itu sendiri: satu transaksi yang sudah diberi
     * rating dianggap mewakili satu "profil selera" - setiap produk yang
     * dibeli dalam transaksi itu dianggap punya rating yang sama dengan
     * rating keseluruhan transaksinya. Ini pendekatan yang paling masuk
     * akal dengan data yang tersedia, tapi tetap sebuah simplifikasi -
     * idealnya tiap produk punya rating sendiri-sendiri, bukan satu rating
     * untuk seluruh keranjang. Skor pun sudah dihitung dan disebutkan
     * secara jujur pada BAB II / BAB IV laporan skripsi.
     *
     * @return Collection<int, array{produk: Produk, skor: float, metode: string}>
     */
    private function collaborativeFiltering(int $produkId, int $limit): Collection
    {
        $vektorRating = $this->ambilVektorRatingPerTransaksi();

        // Transaksi mana saja yang pernah "menilai" produk ini (yaitu
        // transaksi yang membeli produk ini DAN sudah diberi rating).
        $transaksiPemilikProduk = $vektorRating->filter(
            fn (array $vektor) => array_key_exists($produkId, $vektor)
        );

        if ($transaksiPemilikProduk->count() < self::MINIMAL_TRANSAKSI_PEMBANDING) {
            return collect();
        }

        // Hitung similarity antara SETIAP transaksi lain dengan kumpulan
        // transaksi pemilik produk ini, lalu simpan similarity tertinggi
        // yang didapat tiap transaksi lain (semakin mirip pola ratingnya,
        // semakin besar kemungkinan transaksi itu jadi acuan rekomendasi).
        $similarityPerTransaksi = [];

        foreach ($vektorRating as $transaksiId => $vektorLain) {
            if ($transaksiPemilikProduk->has($transaksiId)) {
                continue; // jangan bandingkan transaksi dengan dirinya sendiri
            }

            $similaritasTerbaik = 0.0;
            foreach ($transaksiPemilikProduk as $vektorPemilik) {
                // Dua transaksi tanpa satu pun produk yang sama tidak punya
                // titik pembanding. Kalau tetap dihitung, jaraknya 0 dan
                // similarity-nya jadi 1 (paling mirip) - padahal justru
                // sama sekali tidak bisa dibandingkan. Jadi dilewati saja.
                if (! array_intersect_key($vektorPemilik, $vektorLain)) {
                    continue;
                }

                $jarak = $this->jarakEuclidean($vektorPemilik, $vektorLain);
                $similaritasTerbaik = max($similaritasTerbaik, 1 / (1 + $jarak));
            }

            if ($similaritasTerbaik > 0) {
                $similarityPerTransaksi[$transaksiId] = $similaritasTerbaik;
            }
        }

        if (empty($similarityPerTransaksi)) {
            return collect();
        }

        // Ambil K transaksi "tetangga" paling mirip.
        arsort($similarityPerTransaksi);
        $kTetanggaTerdekat = array_slice($similarityPerTransaksi, 0, self::K_TETANGGA, true);

        // Kumpulkan produk apa saja yang dibeli para tetangga ini (selain
        // produk yang sedang kita cari rekomendasinya), sambil menjumlahkan
        // bobot similarity sebagai skor mentahnya.
        $skorMentahPerProduk = [];
        foreach ($kTetanggaTerdekat as $transaksiId => $similarity) {
            foreach ($vektorRating[$transaksiId] as $idProdukLain => $ratingTakDipakai) {
                if ($idProdukLain === $produkId) {
                    continue;
                }
                $skorMentahPerProduk[$idProdukLain] = ($skorMentahPerProduk[$idProdukLain] ?? 0) + $similarity;
            }
        }

        return $this->skorMentahMenjadiPersentase($skorMentahPerProduk, $limit, 'knn');
    }

    /**
     * Ambil "vektor rating" tiap transaksi yang sudah dinilai pelanggan.
     * Bentuknya: [transaksi_id => [produk_id => rating, produk_id => rating, ...]]
     *
     * @return Collection<int, array<int, int>>
     */
    private function ambilVektorRatingPerTransaksi(): Collection
    {
        return DB::table('rating_komen')
            ->join('detail_transaksi', 'rating_komen.transaksi_id', '=', 'detail_transaksi.transaksi_id')
            ->select('rating_komen.transaksi_id', 'detail_transaksi.produk_id', 'rating_komen.rating')
            ->get()
            ->groupBy('transaksi_id')
            ->map(fn (Collection $baris) => $baris->pluck('rating', 'produk_id')->all());
    }

    /**
     * Jarak Euclidean antar dua vektor rating, tapi cuma dihitung pada
     * dimensi (produk) yang muncul di KEDUA vektor - persis seperti versi
     * lama, supaya dua transaksi yang barangnya sama sekali berbeda tidak
     * dianggap "jauh sekali" hanya karena tidak ada titik pembanding.
     */
    private function jarakEuclidean(array $vektorA, array $vektorB): float
    {
        $jumlahKuadrat = 0;
        foreach ($vektorA as $produkId => $nilaiA) {
            if (array_key_exists($produkId, $vektorB)) {
                $jumlahKuadrat += ($nilaiA - $vektorB[$produkId]) ** 2;
            }
        }

        return sqrt($jumlahKuadrat);
    }

    /**
     * METODE 2: Co-purchase - lihat produk apa yang paling sering muncul
     * bareng produk ini di transaksi mana pun (tanpa peduli rating sama
     * sekali). Ini yang di versi lama disebut "rekomendasi real-time" dan
     * salah kaprah diberi label KNN, padahal secara teknis ini market
     * basket analysis biasa. Di sini labelnya diluruskan jadi "co_purchase".
     *
     * @return Collection<int, array{produk: Produk, skor: float, metode: string}>
     */
    private function coPurchase(int $produkId, int $limit): Collection
    {
        $jumlahKemunculanBersama = DB::table('detail_transaksi as dt1')
            ->join('detail_transaksi as dt2', 'dt1.transaksi_id', '=', 'dt2.transaksi_id')
            ->where('dt1.produk_id', $produkId)
            ->where('dt2.produk_id', '!=', $produkId)
            ->select('dt2.produk_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('dt2.produk_id')
            ->pluck('total', 'produk_id')
            ->all();

        return $this->skorMentahMenjadiPersentase($jumlahKemunculanBersama, $limit, 'co_purchase');
    }

    /**
     * Ubah angka skor mentah (bisa berupa jumlah similarity atau jumlah
     * kemunculan bersama) menjadi persentase 0-100 yang gampang dibaca
     * pelanggan, lalu ambil {$limit} produk dengan skor tertinggi dan
     * lengkapi dengan data produknya. Kalau totalnya nol/kosong, langsung
     * balikin koleksi kosong - JANGAN pernah diisi angka bawaan seperti
     * versi lama (temuan F07 di laporan audit).
     *
     * @param  array<int, float|int>  $skorMentahPerProduk
     * @return Collection<int, array{produk: Produk, skor: float, metode: string}>
     */
    private function skorMentahMenjadiPersentase(array $skorMentahPerProduk, int $limit, string $metode): Collection
    {
        if (empty($skorMentahPerProduk)) {
            return collect();
        }

        arsort($skorMentahPerProduk);
        $topProdukId = array_slice(array_keys($skorMentahPerProduk), 0, $limit);
        $totalSkor = array_sum($skorMentahPerProduk);

        $produkMap = Produk::whereIn('id', $topProdukId)->get()->keyBy('id');

        return collect($topProdukId)
            ->filter(fn (int $id) => $produkMap->has($id))
            ->map(fn (int $id) => [
                'produk' => $produkMap[$id],
                'skor' => round(($skorMentahPerProduk[$id] / $totalSkor) * 100, 2),
                'metode' => $metode,
            ])
            ->values();
    }

    /**
     * Simpan hasil rekomendasi ke tabel log_rekomendasi supaya admin punya
     * riwayat yang bisa ditelusuri. Dipanggil dari perintah Artisan
     * "rekomendasi:perbarui" (lihat app/Console/Commands), yang
     * menggantikan cron_update.php dan update_rekomendasi.php versi lama -
     * dua berkas itu dulu adalah halaman web publik tanpa autentikasi
     * (temuan F10 di laporan audit); sekarang cuma bisa dijalankan lewat
     * command line / scheduler Laravel, tidak lagi bisa diakses browser.
     */
    public function perbaruiSemuaLog(): int
    {
        $disimpan = 0;
        $hariIni = now()->toDateString();

        foreach (Produk::pluck('id') as $produkId) {
            foreach ($this->rekomendasikanUntukProduk($produkId, 3) as $rekomendasi) {
                $sudahAda = LogRekomendasi::where('produk_asal_id', $produkId)
                    ->where('produk_rekomendasi_id', $rekomendasi['produk']->id)
                    ->whereDate('tanggal', $hariIni)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                LogRekomendasi::create([
                    'produk_asal_id' => $produkId,
                    'produk_rekomendasi_id' => $rekomendasi['produk']->id,
                    'skor' => $rekomendasi['skor'],
                    'metode' => $rekomendasi['metode'],
                    'tanggal' => now(),
                ]);

                $disimpan++;
            }
        }

        return $disimpan;
    }
}
