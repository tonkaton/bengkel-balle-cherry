<?php

namespace App\Services;

use App\Models\LogRekomendasi;
use App\Models\Produk;
use App\Models\Servis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RekomendasiService - satu-satunya "otak" rekomendasi di aplikasi ini.
 * Sengaja disatukan di sini karena versi PHP lama (bengkel_knn2) punya
 * TIGA mekanisme berbeda yang semuanya diberi label "KNN", padahal cuma satu
 * yang benar-benar KNN (lihat Laporan Audit Teknis temuan F05 dan F08).
 * Supaya kesalahan itu tidak terulang, di sini setiap metode punya nama dan
 * dokumentasi yang jujur soal metode apa yang sebenarnya dipakai.
 *
 * Item yang dihitung ada dua jenis: sparepart (produk) dan jasa servis.
 * Keduanya bisa muncul di transaksi yang sama (transaksi hasil booking
 * servis), jadi di dalam service ini setiap item dikenali dengan kunci
 * "p:{id}" untuk produk dan "s:{id}" untuk servis. Dengan begitu oli bisa
 * merekomendasikan Servis Ringan, dan Servis Ringan bisa merekomendasikan
 * oli, memakai perhitungan yang persis sama.
 *
 * Ada dua strategi rekomendasi:
 *
 * 1. Collaborative filtering (K-Nearest Neighbor asli) - dipakai kalau data
 *    rating sudah cukup banyak. Menghitung jarak Euclidean antar transaksi
 *    berdasarkan pola rating, lalu melihat item apa saja yang ada di
 *    transaksi-transaksi "tetangga" yang paling mirip.
 * 2. Co-purchase (item yang sering dibeli bersamaan) - dipakai sebagai
 *    cadangan kalau data rating belum cukup untuk KNN yang bisa diandalkan.
 *    Ini BUKAN K-Nearest Neighbor, jadi selalu diberi label "co_purchase",
 *    bukan "knn", di kolom metode pada tabel log_rekomendasi.
 *
 * Prinsip paling penting di seluruh service ini: skor yang dikembalikan
 * SELALU hasil hitungan asli dari data yang ada. Kalau datanya tidak cukup,
 * yang dikembalikan adalah koleksi kosong - bukan angka acak, bukan angka
 * bawaan 95 seperti temuan F05/F07 di laporan audit.
 *
 * Setiap hasil rekomendasi berbentuk:
 * ['item' => Produk|Servis, 'jenis' => 'produk'|'servis', 'kunci' => 'p:1', 'skor' => float, 'metode' => string]
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

    /** Rekomendasi untuk satu sparepart (dipakai keranjang dan pembaruan log). */
    public function rekomendasikanUntukProduk(int $produkId, int $limit = 3): Collection
    {
        return $this->rekomendasikanUntukItem('p:'.$produkId, $limit);
    }

    /** Rekomendasi untuk satu jasa servis (dipakai halaman booking servis). */
    public function rekomendasikanUntukServis(int $servisId, int $limit = 3): Collection
    {
        return $this->rekomendasikanUntukItem('s:'.$servisId, $limit);
    }

    /**
     * Titik masuk utama: cari item lain yang layak direkomendasikan
     * berdampingan dengan satu item tertentu ("p:{id}" atau "s:{id}").
     */
    public function rekomendasikanUntukItem(string $kunci, int $limit = 3): Collection
    {
        $hasilKnn = $this->collaborativeFiltering($kunci, $limit);

        if ($hasilKnn->isNotEmpty()) {
            return $hasilKnn;
        }

        return $this->coPurchase($kunci, $limit);
    }

    /**
     * Versi untuk keranjang belanja: gabungkan rekomendasi dari semua
     * produk yang sedang ada di keranjang, lalu urutkan berdasarkan skor
     * gabungan tertinggi. Produk yang sudah ada di keranjang tidak akan
     * direkomendasikan lagi (percuma menyarankan barang yang sudah dibeli).
     *
     * Skor gabungan = rata-rata skor item itu terhadap seluruh produk di
     * keranjang (produk yang tidak merekomendasikannya dihitung 0). Item yang
     * relevan dari beberapa produk sekaligus otomatis naik peringkatnya,
     * skornya tidak pernah melebihi 100%, dan untuk keranjang berisi satu
     * produk hasilnya sama persis dengan rekomendasikanUntukProduk().
     *
     * @param  list<int>  $produkIdDiKeranjang
     */
    public function rekomendasikanUntukKeranjang(array $produkIdDiKeranjang, int $limit = 3): Collection
    {
        if (empty($produkIdDiKeranjang)) {
            return collect();
        }

        $kunciKeranjang = array_map(fn (int $id) => 'p:'.$id, $produkIdDiKeranjang);

        // Array biasa, bukan Collection: elemen Collection tidak bisa diubah
        // langsung lewat $gabungan[$kunci]['skor'] += ... (PHP menolaknya).
        $gabungan = [];

        foreach ($kunciKeranjang as $kunciAsal) {
            foreach ($this->rekomendasikanUntukItem($kunciAsal, $limit) as $item) {
                if (in_array($item['kunci'], $kunciKeranjang, true)) {
                    continue;
                }

                if (isset($gabungan[$item['kunci']])) {
                    $gabungan[$item['kunci']]['skor'] += $item['skor'];
                    if ($item['metode'] === 'knn') {
                        $gabungan[$item['kunci']]['metode'] = 'knn';
                    }
                } else {
                    $gabungan[$item['kunci']] = $item;
                }
            }
        }

        $jumlahProduk = count($kunciKeranjang);

        return collect($gabungan)
            ->map(fn (array $item) => array_merge($item, ['skor' => round($item['skor'] / $jumlahProduk, 2)]))
            ->sortByDesc('skor')
            ->take($limit)
            ->values();
    }

    /**
     * METODE 1: Collaborative filtering pakai jarak Euclidean - ini yang
     * secara teknis benar-benar layak disebut K-Nearest Neighbor.
     *
     * Karena aplikasi ini tidak punya akun pelanggan (checkout bersifat
     * anonim, seperti kasir toko biasa), maka "pengguna" yang dipakai untuk
     * KNN adalah TRANSAKSI itu sendiri: satu transaksi yang sudah diberi
     * rating dianggap mewakili satu "profil selera" - setiap item yang ada
     * dalam transaksi itu dianggap punya rating yang sama dengan rating
     * keseluruhan transaksinya. Ini pendekatan yang paling masuk akal dengan
     * data yang tersedia, tapi tetap sebuah simplifikasi - idealnya tiap
     * item punya rating sendiri-sendiri, bukan satu rating untuk seluruh
     * transaksi. Skor pun sudah dihitung dan disebutkan secara jujur pada
     * BAB II / BAB IV laporan skripsi.
     */
    private function collaborativeFiltering(string $kunci, int $limit): Collection
    {
        $vektorRating = $this->ambilVektorRatingPerTransaksi();

        // Transaksi mana saja yang pernah "menilai" item ini (yaitu
        // transaksi yang memuat item ini DAN sudah diberi rating).
        $transaksiPemilikItem = $vektorRating->filter(
            fn (array $vektor) => array_key_exists($kunci, $vektor)
        );

        if ($transaksiPemilikItem->count() < self::MINIMAL_TRANSAKSI_PEMBANDING) {
            return collect();
        }

        // Hitung similarity antara SETIAP transaksi lain dengan kumpulan
        // transaksi pemilik item ini, lalu simpan similarity tertinggi
        // yang didapat tiap transaksi lain (semakin mirip pola ratingnya,
        // semakin besar kemungkinan transaksi itu jadi acuan rekomendasi).
        $similarityPerTransaksi = [];

        foreach ($vektorRating as $transaksiId => $vektorLain) {
            if ($transaksiPemilikItem->has($transaksiId)) {
                continue; // jangan bandingkan transaksi dengan dirinya sendiri
            }

            $similaritasTerbaik = 0.0;
            foreach ($transaksiPemilikItem as $vektorPemilik) {
                // Dua transaksi tanpa satu pun item yang sama tidak punya
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

        // Kumpulkan item apa saja yang ada di transaksi para tetangga ini
        // (selain item yang sedang kita cari rekomendasinya), sambil
        // menjumlahkan bobot similarity sebagai skor mentahnya.
        $skorMentahPerItem = [];
        foreach ($kTetanggaTerdekat as $transaksiId => $similarity) {
            foreach ($vektorRating[$transaksiId] as $kunciLain => $ratingTakDipakai) {
                if ($kunciLain === $kunci) {
                    continue;
                }
                $skorMentahPerItem[$kunciLain] = ($skorMentahPerItem[$kunciLain] ?? 0) + $similarity;
            }
        }

        return $this->skorMentahMenjadiPersentase($skorMentahPerItem, $limit, 'knn');
    }

    /**
     * Ambil "vektor rating" tiap transaksi yang sudah dinilai pelanggan.
     * Bentuknya: [transaksi_id => ["p:3" => rating, "s:1" => rating, ...]]
     *
     * @return Collection<int, array<string, int>>
     */
    private function ambilVektorRatingPerTransaksi(): Collection
    {
        return DB::table('rating_komen')
            ->join('detail_transaksi', 'rating_komen.transaksi_id', '=', 'detail_transaksi.transaksi_id')
            ->select('rating_komen.transaksi_id', 'detail_transaksi.produk_id', 'detail_transaksi.servis_id', 'rating_komen.rating')
            ->get()
            ->groupBy('transaksi_id')
            ->map(fn (Collection $baris) => $baris
                ->mapWithKeys(fn ($b) => [$this->kunciDetail($b) => $b->rating])
                ->all());
    }

    /**
     * Jarak Euclidean antar dua vektor rating, tapi cuma dihitung pada
     * dimensi (item) yang muncul di KEDUA vektor - persis seperti versi
     * lama, supaya dua transaksi yang isinya sama sekali berbeda tidak
     * dianggap "jauh sekali" hanya karena tidak ada titik pembanding.
     */
    private function jarakEuclidean(array $vektorA, array $vektorB): float
    {
        $jumlahKuadrat = 0;
        foreach ($vektorA as $kunci => $nilaiA) {
            if (array_key_exists($kunci, $vektorB)) {
                $jumlahKuadrat += ($nilaiA - $vektorB[$kunci]) ** 2;
            }
        }

        return sqrt($jumlahKuadrat);
    }

    /**
     * METODE 2: Co-purchase - lihat item apa yang paling sering muncul
     * bareng item ini di transaksi mana pun (tanpa peduli rating sama
     * sekali). Ini yang di versi lama disebut "rekomendasi real-time" dan
     * salah kaprah diberi label KNN, padahal secara teknis ini market
     * basket analysis biasa. Di sini labelnya diluruskan jadi "co_purchase".
     */
    private function coPurchase(string $kunci, int $limit): Collection
    {
        [$kolom, $id] = $this->kolomDetail($kunci);

        $jumlahKemunculanBersama = DB::table('detail_transaksi as dt1')
            ->join('detail_transaksi as dt2', 'dt1.transaksi_id', '=', 'dt2.transaksi_id')
            ->where("dt1.{$kolom}", $id)
            ->where(fn ($q) => $q->whereNull("dt2.{$kolom}")->orWhere("dt2.{$kolom}", '!=', $id))
            ->select('dt2.produk_id', 'dt2.servis_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('dt2.produk_id', 'dt2.servis_id')
            ->get()
            ->mapWithKeys(fn ($b) => [$this->kunciDetail($b) => (int) $b->total])
            ->all();

        return $this->skorMentahMenjadiPersentase($jumlahKemunculanBersama, $limit, 'co_purchase');
    }

    /**
     * Ubah angka skor mentah (bisa berupa jumlah similarity atau jumlah
     * kemunculan bersama) menjadi persentase 0-100 yang gampang dibaca
     * pelanggan, lalu ambil {$limit} item dengan skor tertinggi dan
     * lengkapi dengan data produk/servisnya. Kalau totalnya nol/kosong,
     * langsung balikin koleksi kosong - JANGAN pernah diisi angka bawaan
     * seperti versi lama (temuan F07 di laporan audit).
     *
     * @param  array<string, float|int>  $skorMentahPerItem
     */
    private function skorMentahMenjadiPersentase(array $skorMentahPerItem, int $limit, string $metode): Collection
    {
        if (empty($skorMentahPerItem)) {
            return collect();
        }

        arsort($skorMentahPerItem);
        $kunciTeratas = array_slice(array_keys($skorMentahPerItem), 0, $limit);
        $totalSkor = array_sum($skorMentahPerItem);
        $model = $this->muatItem($kunciTeratas);

        return collect($kunciTeratas)
            ->filter(fn (string $k) => isset($model[$k]))
            ->map(fn (string $k) => [
                'item' => $model[$k],
                'jenis' => str_starts_with($k, 's:') ? 'servis' : 'produk',
                'kunci' => $k,
                'skor' => round(($skorMentahPerItem[$k] / $totalSkor) * 100, 2),
                'metode' => $metode,
            ])
            ->values();
    }

    /**
     * Ambil model Produk/Servis untuk sekumpulan kunci item.
     *
     * @param  list<string>  $kunci
     * @return array<string, Produk|Servis>
     */
    private function muatItem(array $kunci): array
    {
        $id = fn (string $awalan) => array_map(fn ($k) => (int) substr($k, 2), array_filter($kunci, fn ($k) => str_starts_with($k, $awalan)));
        $hasil = [];
        foreach (Produk::whereIn('id', $id('p:'))->get() as $produk) {
            $hasil['p:'.$produk->id] = $produk;
        }
        foreach (Servis::whereIn('id', $id('s:'))->get() as $servis) {
            $hasil['s:'.$servis->id] = $servis;
        }

        return $hasil;
    }

    /** Kunci item untuk satu baris detail_transaksi: "p:{produk_id}" atau "s:{servis_id}". */
    private function kunciDetail(object $baris): string
    {
        return $baris->servis_id ? 's:'.$baris->servis_id : 'p:'.$baris->produk_id;
    }

    /** Kolom detail_transaksi dan id untuk sebuah kunci item. */
    private function kolomDetail(string $kunci): array
    {
        return [str_starts_with($kunci, 's:') ? 'servis_id' : 'produk_id', (int) substr($kunci, 2)];
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
        $semuaItem = Produk::pluck('id')->map(fn ($id) => ['produk', $id])
            ->concat(Servis::pluck('id')->map(fn ($id) => ['servis', $id]));

        foreach ($semuaItem as [$jenis, $id]) {
            foreach ($this->rekomendasikanUntukItem(($jenis === 'servis' ? 's:' : 'p:').$id, 3) as $rekomendasi) {
                $sudahAda = LogRekomendasi::where('asal_type', $jenis)->where('asal_id', $id)
                    ->where('rekomendasi_type', $rekomendasi['jenis'])->where('rekomendasi_id', $rekomendasi['item']->id)
                    ->whereDate('tanggal', $hariIni)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                LogRekomendasi::create([
                    'asal_type' => $jenis,
                    'asal_id' => $id,
                    'rekomendasi_type' => $rekomendasi['jenis'],
                    'rekomendasi_id' => $rekomendasi['item']->id,
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
