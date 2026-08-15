<?php

namespace App\Services\Shipping;

use App\Models\ShippingCourier;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use MadeByClowd\Nusantara\Models\Province;
use MadeByClowd\Nusantara\Models\Regency;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ShippingRateImportService
{
    /**
     * Alias provinsi yang digunakan oleh price list.
     */
    protected array $provinceAliases = [
        'NTB' => 'Nusa Tenggara Barat',
        'NTT' => 'Nusa Tenggara Timur',
        'DIY' => 'Daerah Istimewa Yogyakarta',
        'DKI JAKARTA' => 'Daerah Khusus Ibukota Jakarta',
    ];

    protected array $sentralProvinceAliases = [

        'JAWA BARAT' => 'Jawa Barat',
        'JAWA TENGAH' => 'Jawa Tengah',
        'JAWA TIMUR' => 'Jawa Timur',
        'BANTEN' => 'Banten',
        'BALI' => 'Bali',
    ];

    protected array $sentralRegencyAliases = [

        // Jawa Barat
        'SUKABUMI KOTA' => 'KOTA SUKABUMI',
        'KARAWANG KOTA' => 'KABUPATEN KARAWANG',
        'KOTA PURWAKARTA' => 'KABUPATEN PURWAKARTA',
        'KOTA GARUT' => 'KABUPATEN GARUT',
        'KOTA TASIKMALAYA' => 'KOTA TASIKMALAYA',
        'KOTA CIREBON' => 'KOTA CIREBON',

        'KAB BANJARAN' => 'KABUPATEN BANDUNG',
        'KAB CIMAHI' => 'KOTA CIMAHI',

        // Jawa Tengah
        'CILACAP KOTA' => 'KABUPATEN CILACAP',
        'KOTA SOLO' => 'KOTA SURAKARTA',
        'KOTA KUDUS' => 'KABUPATEN KUDUS',
        'KULONPROGO' => 'KABUPATEN KULON PROGO',
        'WONOSOBO KOTA' => 'KABUPATEN WONOSOBO',
        'KAB SALATIGA' => 'KOTA SALATIGA',

        // Jawa Timur
        'KAB SURABAYA' => 'KOTA SURABAYA',
        'KOTA SIDOARJO' => 'KABUPATEN SIDOARJO',
        'GRESIK KOTA' => 'KABUPATEN GRESIK',
        'KOTA JEMBER' => 'KABUPATEN JEMBER',
        'KOTA MOJOKERTO' => 'KOTA MOJOKERTO',
        'KOTA PASURUAN' => 'KOTA PASURUAN',
        'KOTA BATU' => 'KOTA BATU',

        // Bali
        'KOTA BADUNG' => 'KABUPATEN BADUNG',
        'KAB BULELENG' => 'KABUPATEN BULELENG',
    ];

    /**
     * Import price list J&T Cargo.
     */
    public function importJnt(string $filePath): int
    {
        $courier = ShippingCourier::query()
            ->where('code', 'jnt_cargo')
            ->first();

        if (!$courier) {
            throw new RuntimeException(
                'Courier J&T Cargo belum tersedia.'
            );
        }

        $rows = $this->readSpreadsheet($filePath);

        if (empty($rows)) {
            throw new RuntimeException(
                'File price list tidak memiliki data.'
            );
        }

        $preparedRows = [];

        foreach ($rows as $index => $row) {

            /*
             * File J&T:
             *
             * Kolom A = KOTA/KAB
             * Kolom B = PROVINSI
             * Kolom C = HARGA ONGKIR PER-KG
             *
             * Data dimulai dari baris Excel 4.
             */
            $excelRow = $index + 4;

            $cityName = trim((string) ($row[0] ?? ''));
            $provinceName = trim((string) ($row[1] ?? ''));
            $price = $row[2] ?? null;

            /*
             * Lewati baris benar-benar kosong.
             */
            if (
                $cityName === '' &&
                $provinceName === '' &&
                $price === null
            ) {
                continue;
            }

            if ($cityName === '' || $provinceName === '') {
                throw new RuntimeException(
                    "Data J&T pada baris {$excelRow} tidak lengkap."
                );
            }

            if (!is_numeric($price)) {
                throw new RuntimeException(
                    "Harga J&T pada baris {$excelRow} tidak valid."
                );
            }

            /*
             * Cari provinsi.
             */
            $province = $this->findProvince($provinceName);

            if (!$province) {
                throw new RuntimeException(
                    "Provinsi '{$provinceName}' pada baris {$excelRow} tidak ditemukan."
                );
            }

            /*
             * Cari kabupaten/kota berdasarkan provinsi.
             */
            $regency = $this->findRegency(
                $province,
                $cityName
            );

            if (!$regency) {
                throw new RuntimeException(
                    "Kabupaten/Kota '{$cityName}' ({$provinceName}) pada baris {$excelRow} tidak ditemukan."
                );
            }

            $preparedRows[] = [
                'courier_id' => $courier->id,
                'regency_id' => $regency->id,
                'rate_type' => 'per_kg',
                'price_per_kg' => (float) $price,
                'first_price' => null,
                'additional_price_per_kg' => null,
                'is_active' => true,
            ];
        }

        /*
         * Semua data sudah divalidasi terlebih dahulu.
         *
         * Jika satu saja gagal, transaction tidak dijalankan.
         */
        return DB::transaction(function () use (
            $preparedRows
        ) {

            foreach ($preparedRows as $data) {

                ShippingRate::updateOrCreate(
                    [
                        'courier_id' => $data['courier_id'],
                        'regency_id' => $data['regency_id'],
                    ],
                    $data
                );
            }

            return count($preparedRows);
        });
    }

    /**
     * Membaca file spreadsheet.
     */
    protected function readSpreadsheet(string $filePath, int $startRow = 1): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $worksheet = $spreadsheet->getActiveSheet();

        return $worksheet
            ->toArray(
                null,
                true,
                true,
                false
            );
    }

    /**
     * Cari provinsi berdasarkan nama.
     */
    protected function findProvince(
        string $name
    ): ?Province {

        $normalized = $this->normalize($name);

        $actualName = $this->provinceAliases[$normalized]
            ?? $name;

        return Province::query()
            ->whereRaw(
                'UPPER(name) = ?',
                [mb_strtoupper($actualName)]
            )
            ->first();
    }

    /**
     * Cari kabupaten/kota berdasarkan provinsi.
     */
    protected function findRegency(
        Province $province,
        string $name
    ): ?Regency {

        $normalized = $this->normalizeRegencyName($name);

        /*
        * =========================================================
        * TAHAP 1
        * Exact match berdasarkan province_id + nama wilayah.
        * =========================================================
        */
        $regency = Regency::query()
            ->where('province_id', $province->id)
            ->get()
            ->first(
                function (Regency $regency) use ($normalized) {

                    return $this->normalizeRegencyName(
                        $regency->name
                    ) === $normalized;
                }
            );

        if ($regency) {
            return $regency;
        }

        /*
        * =========================================================
        * TAHAP 2
        * Match berdasarkan nama tanpa prefix KAB / KOTA,
        * tetapi masih dibatasi oleh province_id.
        *
        * Contoh:
        *
        * XLSX:
        *   Kota Tanjung Balai
        *
        * Database:
        *   Kota Tanjungbalai
        *
        * =========================================================
        */
        $normalizedWithoutPrefix = preg_replace(
            '/^(KAB|KOTA)\s+/',
            '',
            $normalized
        );

        $regency = Regency::query()
            ->where('province_id', $province->id)
            ->get()
            ->first(
                function (Regency $regency) use (
                    $normalizedWithoutPrefix
                ) {

                    $databaseName = $this->normalizeRegencyName(
                        $regency->name
                    );

                    $databaseNameWithoutPrefix = preg_replace(
                        '/^(KAB|KOTA)\s+/',
                        '',
                        $databaseName
                    );

                    return $databaseNameWithoutPrefix ===
                        $normalizedWithoutPrefix;
                }
            );

        if ($regency) {
            return $regency;
        }

        /*
        * =========================================================
        * TAHAP 3 - FALLBACK GLOBAL
        * =========================================================
        *
        * Digunakan apabila struktur provinsi pada price list
        * berbeda dengan struktur wilayah Indonesia terbaru.
        *
        * Contoh:
        *
        * Price list lama:
        *   Kab Nabire | Papua
        *
        * Database terbaru:
        *   Kabupaten Nabire | Papua Tengah
        *
        * Karena "Nabire" hanya memiliki SATU exact match
        * di seluruh database, kita dapat menggunakannya.
        *
        * =========================================================
        */

        $globalMatches = Regency::query()
            ->get()
            ->filter(
                function (Regency $regency) use (
                    $normalized,
                    $normalizedWithoutPrefix
                ) {

                    $databaseName = $this->normalizeRegencyName(
                        $regency->name
                    );

                    /*
                    * Coba exact match terlebih dahulu.
                    */
                    if ($databaseName === $normalized) {
                        return true;
                    }

                    /*
                    * Kemudian coba match tanpa prefix
                    * KAB / KOTA.
                    */
                    $databaseNameWithoutPrefix = preg_replace(
                        '/^(KAB|KOTA)\s+/',
                        '',
                        $databaseName
                    );

                    return $databaseNameWithoutPrefix ===
                        $normalizedWithoutPrefix;
                }
            )
            ->values();

        /*
        * Hanya boleh menggunakan fallback jika hasilnya
        * benar-benar unik.
        *
        * Jika lebih dari satu hasil, jangan menebak.
        */
        if ($globalMatches->count() === 1) {
            return $globalMatches->first();
        }

        /*
        * Jika tidak ditemukan atau hasilnya ambigu,
        * kembalikan null agar importer memberikan error
        * yang jelas.
        */
        return null;
    }

    /**
     * Normalisasi nama provinsi.
     */
    protected function normalize(string $value): string
    {
        $value = mb_strtoupper(trim($value));

        $value = str_replace(
            ['.', ',', '-', '_'],
            ' ',
            $value
        );

        return preg_replace(
            '/\s+/',
            ' ',
            $value
        );
    }

    /**
     * Normalisasi nama kabupaten/kota.
     *
     * Prefix KAB dan KOTA dipertahankan
     * agar wilayah dengan nama sama tidak tertukar.
     */
    protected function normalizeRegencyName(
        string $value
    ): string {

        $value = mb_strtoupper(trim($value));

        $value = str_replace(
            ['.', ',', '_'],
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        /*
        * Samakan nomenklatur Kabupaten/Kota.
        */

        $value = preg_replace(
            '/^KOTA\s+ADMINISTRASI\s+/',
            'KOTA ',
            $value
        );

        $value = preg_replace(
            '/^KABUPATEN\s+ADMINISTRASI\s+/',
            'KAB ',
            $value
        );

        $value = preg_replace(
            '/^KABUPATEN\s+/',
            'KAB ',
            $value
        );

        $value = preg_replace(
            '/^KAB\s+/',
            'KAB ',
            $value
        );

        $value = preg_replace(
            '/^KOTA\s+/',
            'KOTA ',
            $value
        );

        /*
        * Jakarta pada price list tidak menggunakan
        * prefix KOTA.
        */
        if (str_starts_with($value, 'JAKARTA ')) {
            $value = 'KOTA ' . $value;
        }

        /*
        * Variasi penulisan nama wilayah.
        *
        * XLSX:
        * KOTA TANJUNG BALAI
        *
        * Nusantara:
        * KOTA TANJUNGBALAI
        */
        $value = str_replace(
            'TANJUNG BALAI',
            'TANJUNGBALAI',
            $value
        );

        $value = preg_replace(
            '/\s+DAN\s+/',
            ' ',
            $value
        );

        /*
        * Normalisasi spasi sekali lagi setelah
        * penghapusan kata DAN.
        */
        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        return trim($value);
    }

    /**
     * Import price list Sentral Cargo.
     *
     * Format:
     * - Kolom A = kelompok/provinsi
     * - Kolom B = zona
     * - Kolom C = kota/kabupaten
     * - Kolom D = harga 1-10 Kg pertama
     * - Kolom E = harga tambahan per Kg
     */
    public function importSentral(string $filePath): array
    {
        $courier = ShippingCourier::query()
            ->where('code', 'sentral_cargo')
            ->first();

        if (!$courier) {
            throw new RuntimeException(
                'Courier Sentral Cargo belum tersedia.'
            );
        }

        $rows = $this->readSentralSpreadsheet($filePath);

        if (empty($rows)) {
            throw new RuntimeException(
                'File price list Sentral Cargo tidak memiliki data.'
            );
        }

        $preparedRates = [];
        $skippedRows = [];

        foreach ($rows as $row) {

            $excelRow = $row['excel_row'];

            $provinceGroup = trim(
                (string) ($row['province'] ?? '')
            );

            $destination = trim(
                (string) ($row['city'] ?? '')
            );

            $firstPrice = $row['first_price'] ?? null;
            $additionalPrice = $row['additional_price'] ?? null;

            /*
            * Lewati baris kosong.
            */
            if (
                $provinceGroup === '' &&
                $destination === ''
            ) {
                continue;
            }

            if ($destination === '') {
                throw new RuntimeException(
                    "Tujuan pada baris {$excelRow} tidak ditemukan."
                );
            }

            /*
            * Sentral Cargo dapat memiliki beberapa
            * tujuan dalam satu cell.
            */
            $destinations = $this->splitSentralDestinations(
                $destination
            );

            /*
            * "Sesuai Sistem" atau nilai non-numeric
            * tidak dimasukkan ke database.
            */
            $firstPrice = $this->parseSentralPrice(
                $firstPrice
            );

            $additionalPrice = $this->parseSentralPrice(
                $additionalPrice
            );

            /*
            * Jika salah satu harga tidak dapat diproses,
            * lewati baris tersebut.
            */
            if (
                $firstPrice === null ||
                $additionalPrice === null
            ) {

                $skippedRows[] = [
                    'excel_row' => $excelRow,
                    'destination' => $destination,
                    'reason' => 'Harga berupa "Sesuai Sistem" atau format harga tidak dikenali.',
                ];

                continue;
            }

            /*
            * Proses setiap tujuan.
            */
            foreach ($destinations as $destinationName) {

                $regencies = $this->findSentralRegencies(
                    $destinationName
                );

                if ($regencies->isEmpty()) {
                    throw new RuntimeException(
                        "Tujuan '{$destinationName}' pada baris {$excelRow} tidak ditemukan."
                    );
                }

                foreach ($regencies as $regency) {

                    $key = $courier->id . ':' . $regency->id;

                    if (isset($preparedRates[$key])) {

                        /*
                        * Kabupaten/Kota yang sama dapat muncul
                        * beberapa kali karena memiliki zona berbeda.
                        *
                        * Jika terjadi duplicate, jangan dianggap error.
                        *
                        * Sesuai aturan Sentral Cargo:
                        * gunakan harga Next/kg yang paling tinggi.
                        */

                        if (
                            $additionalPrice >
                            $preparedRates[$key]['additional_price_per_kg']
                        ) {

                            $preparedRates[$key]['additional_price_per_kg'] =
                                $additionalPrice;
                        }

                        continue;
                    }

                    $preparedRates[$key] = [
                        'courier_id' => $courier->id,
                        'regency_id' => $regency->id,
                        'rate_type' => 'tiered',
                        'price_per_kg' => null,
                        'first_price' => $firstPrice,
                        'additional_price_per_kg' => $additionalPrice,
                        'is_active' => true,
                    ];
                }
            }
        }

        if (empty($preparedRates)) {
            throw new RuntimeException(
                'Tidak ada tarif Sentral Cargo yang dapat di-import.'
            );
        }

        /*
        * Simpan seluruh tarif dalam satu transaction.
        *
        * Jika ada error, tidak ada data parsial
        * yang masuk ke database.
        */
        $imported = DB::transaction(function () use (
            $preparedRates
        ) {

            foreach ($preparedRates as $data) {

                ShippingRate::updateOrCreate(
                    [
                        'courier_id' => $data['courier_id'],
                        'regency_id' => $data['regency_id'],
                    ],
                    $data
                );
            }

            return count($preparedRates);
        });

        return [
            'imported' => $imported,
            'skipped' => count($skippedRows),
            'skipped_rows' => $skippedRows,
        ];
    }

    public function testParseSentralPrice(
        mixed $value
    ): ?float {
        return $this->parseSentralPrice($value);
    }

    protected function readSentralSpreadsheet(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $worksheet = $spreadsheet->getActiveSheet();

        /*
        * Struktur file Sentral Cargo:
        *
        * A = Provinsi
        * B = Zona Sentral Cargo
        * C = Kota/Kabupaten
        * D = 1-10 Kg Pertama
        * E = Next/kg
        *
        * Header berada di baris 5.
        * Data dimulai dari baris 6.
        */

        $highestRow = $worksheet->getHighestRow();

        $data = $worksheet->rangeToArray(
            'A6:E' . $highestRow,
            null,
            true,
            true,
            false
        );

        $rows = [];

        foreach ($data as $index => $row) {

            /*
            * Karena data dimulai dari Excel row 6,
            * index 0 = Excel row 6.
            */
            $excelRow = $index + 6;

            $rows[] = [
                'excel_row' => $excelRow,

                'province' => trim(
                    (string) ($row[0] ?? '')
                ),

                'zone' => trim(
                    (string) ($row[1] ?? '')
                ),

                'city' => trim(
                    (string) ($row[2] ?? '')
                ),

                'first_price' => $row[3] ?? null,

                'additional_price' => $row[4] ?? null,
            ];
        }

        return $rows;
    }

    protected function isNumericPrice(
        mixed $value
    ): bool {

        if ($value === null) {
            return false;
        }

        /*
        * Jika memang sudah berupa angka.
        */
        if (is_int($value) || is_float($value)) {
            return true;
        }

        /*
        * Normalisasi string harga.
        *
        * Contoh:
        *
        * " Rp 30,000 "
        * "Rp 30.000"
        * "30,000"
        *
        * menjadi angka yang bisa diproses.
        */
        if (is_string($value)) {

            $value = trim($value);

            if ($value === '') {
                return false;
            }

            /*
            * "Sesuai Sistem" dan variasi teks lainnya
            * bukan tarif numerik.
            */
            if (
                !is_numeric($value) &&
                !preg_match('/\d/', $value)
            ) {
                return false;
            }

            /*
            * Hilangkan:
            * - Rp
            * - spasi
            */
            $value = preg_replace(
                '/rp\s*/i',
                '',
                $value
            );

            $value = str_replace(
                ' ',
                '',
                $value
            );

            /*
            * Format file Sentral:
            *
            * Rp 30,000
            *
            * Jadi koma adalah pemisah ribuan.
            */
            $value = str_replace(
                ',',
                '',
                $value
            );

            return is_numeric($value);
        }

        return false;
    }

    protected function parseSentralPrice(
        mixed $value
    ): ?float {

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        /*
        * Hilangkan prefix Rupiah.
        */
        $value = preg_replace(
            '/rp\s*/i',
            '',
            $value
        );

        /*
        * Hilangkan spasi.
        */
        $value = str_replace(
            ' ',
            '',
            $value
        );

        /*
        * File Sentral menggunakan koma
        * sebagai pemisah ribuan.
        *
        * Rp 30,000 -> 30000
        */
        $value = str_replace(
            ',',
            '',
            $value
        );

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function splitSentralDestinations(
        string $value
    ): array {

        $value = str_replace(
            ["\r\n", "\r", "\n"],
            ' ',
            $value
        );

        $parts = preg_split(
            '/\s*,\s*/',
            $value
        );

        return collect($parts)
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function findSentralProvince(
        string $name
    ): ?Province {

        $normalized = $this->normalize($name);

        $actualName =
            $this->sentralProvinceAliases[$normalized]
            ?? $name;

        return Province::query()
            ->whereRaw(
                'LOWER(name) = ?',
                [mb_strtolower($actualName)]
            )
            ->first();
    }

    protected function findRegencyByNormalizedName(
        Province $province,
        string $normalized
    ): ?Regency {

        $regencies = Regency::query()
            ->where('province_id', $province->id)
            ->get();

        /*
        * Exact match.
        */
        $regency = $regencies->first(
            function (Regency $item) use ($normalized) {

                return $this->normalizeRegencyName(
                    $item->name
                ) === $normalized;
            }
        );

        if ($regency) {
            return $regency;
        }

        /*
        * Fallback tanpa prefix KAB/KOTA.
        */
        $withoutPrefix = preg_replace(
            '/^(KAB|KOTA)\s+/',
            '',
            $normalized
        );

        return $regencies->first(
            function (Regency $item) use ($withoutPrefix) {

                $databaseName =
                    $this->normalizeRegencyName(
                        $item->name
                    );

                $databaseWithoutPrefix =
                    preg_replace(
                        '/^(KAB|KOTA)\s+/',
                        '',
                        $databaseName
                    );

                return $databaseWithoutPrefix ===
                    $withoutPrefix;
            }
        );
    }

    protected function findRegencyAcrossIndonesia(
        string $normalized
    ): ?Regency {

        $regencies = Regency::query()->get();

        /*
        * Exact match terlebih dahulu.
        */
        $regency = $regencies->first(
            function (Regency $item) use ($normalized) {

                return $this->normalizeRegencyName(
                    $item->name
                ) === $normalized;
            }
        );

        if ($regency) {
            return $regency;
        }

        /*
        * Fallback tanpa prefix.
        */
        $withoutPrefix = preg_replace(
            '/^(KAB|KOTA)\s+/',
            '',
            $normalized
        );

        return $regencies->first(
            function (Regency $item) use ($withoutPrefix) {

                $name =
                    $this->normalizeRegencyName(
                        $item->name
                    );

                $nameWithoutPrefix =
                    preg_replace(
                        '/^(KAB|KOTA)\s+/',
                        '',
                        $name
                    );

                return $nameWithoutPrefix ===
                    $withoutPrefix;
            }
        );
    }

    protected function normalizeSentralDestination(
        string $value
    ): string {

        $value = mb_strtoupper(
            trim($value)
        );

        $value = str_replace(
            ['.', ',', '-', '_'],
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        $value = preg_replace(
            '/^KABUPATEN\s+/i',
            'KAB ',
            $value
        );

        $value = preg_replace(
            '/^KAB\s+/i',
            'KAB ',
            $value
        );

        $value = preg_replace(
            '/^KOTA\s+/i',
            'KOTA ',
            $value
        );

        return trim($value);
    }

    public function testFindSentralRegencies(
        string $destination
    ): array {
        return $this->findSentralRegencies($destination)
            ->map(fn (Regency $regency) => [
                'id' => $regency->id,
                'province_id' => $regency->province_id,
                'name' => $regency->name,
            ])
            ->values()
            ->all();
    }

    public function testReadSentralSpreadsheet(
        string $filePath
    ): array {
        return array_slice(
            $this->readSentralSpreadsheet($filePath),
            0,
            5
        );
    }

    protected function findSentralRegencies(
        string $destination
    ): \Illuminate\Support\Collection {

        $normalized = $this->normalizeSentralDestination(
            $destination
        );

        $regencies = Regency::query()->get();

        /*
        |--------------------------------------------------------------------------
        | SPECIAL MAPPING
        |--------------------------------------------------------------------------
        */

        $specialMappings = [

            /*
            * Sentral: Kota Jakarta
            *
            * Database Nusantara:
            * Jakarta dibagi menjadi 5 wilayah administratif.
            */
            'KOTA JAKARTA' => [
                '3171',
                '3172',
                '3173',
                '3174',
                '3175',
            ],

            /*
            * Beberapa format khusus yang sudah diketahui.
            */
            'SUKABUMI KOTA' => [
                '3272',
            ],

            'KAB BANJARAN' => [
                '3204',
            ],

            'KOTA SOLO' => [
                // Kota Surakarta
                '3372',
            ],

            'KAB GUNUNG KIDUL' => [
                '3403',
            ],

            'KAB KULONPROGO' => [
                '3401',
            ],
        ];

        if (isset($specialMappings[$normalized])) {

            return Regency::query()
                ->whereIn(
                    'id',
                    $specialMappings[$normalized]
                )
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | 1. EXACT MATCH
        |--------------------------------------------------------------------------
        */

        $regency = $regencies->first(
            function (Regency $regency) use ($normalized) {

                return $this->normalizeSentralDestination(
                    $regency->name
                ) === $normalized;
            }
        );

        if ($regency) {
            return collect([$regency]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. REVERSE PREFIX
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | CIANJUR KOTA
        |       ↓
        | KOTA CIANJUR
        |
        | BOGOR KAB
        |       ↓
        | KAB BOGOR
        */

        $reversedNormalized =
            $this->reverseSentralPrefix($normalized);

        if ($reversedNormalized !== $normalized) {

            $regency = $regencies->first(
                function (Regency $regency) use (
                    $reversedNormalized
                ) {

                    return $this->normalizeSentralDestination(
                        $regency->name
                    ) === $reversedNormalized;
                }
            );

            if ($regency) {
                return collect([$regency]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. MATCH BERDASARKAN NAMA SAJA
        |--------------------------------------------------------------------------
        |
        | Ini penting untuk:
        |
        | CIANJUR KOTA
        | CIANJUR KAB
        | KOTA CIANJUR
        | KAB CIANJUR
        |
        | semuanya dibandingkan berdasarkan:
        |
        | CIANJUR
        */

        $baseName = $this->extractSentralBaseName(
            $normalized
        );

        if ($baseName !== '') {

            $matches = $regencies->filter(
                function (Regency $regency) use ($baseName) {

                    $databaseName =
                        $this->normalizeSentralDestination(
                            $regency->name
                        );

                    $databaseBaseName =
                        $this->extractSentralBaseName(
                            $databaseName
                        );

                    return $databaseBaseName === $baseName;
                }
            )->values();

            /*
            |--------------------------------------------------------------------------
            | 3A. Jika hanya satu wilayah dengan nama tersebut
            |--------------------------------------------------------------------------
            */

            if ($matches->count() === 1) {
                return $matches;
            }

            /*
            |--------------------------------------------------------------------------
            | 3B. Jika input secara eksplisit KAB
            |--------------------------------------------------------------------------
            */

            if (str_starts_with($normalized, 'KAB ')) {

                $kabupaten = $matches->first(
                    function (Regency $regency) {

                        $name =
                            $this->normalizeSentralDestination(
                                $regency->name
                            );

                        return str_starts_with(
                            $name,
                            'KAB '
                        );
                    }
                );

                if ($kabupaten) {
                    return collect([$kabupaten]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3C. Jika input secara eksplisit KOTA
            |--------------------------------------------------------------------------
            */

            if (str_starts_with($normalized, 'KOTA ')) {

                $kota = $matches->first(
                    function (Regency $regency) {

                        $name =
                            $this->normalizeSentralDestination(
                                $regency->name
                            );

                        return str_starts_with(
                            $name,
                            'KOTA '
                        );
                    }
                );

                if ($kota) {
                    return collect([$kota]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3D. Format suffix KOTA / KAB
            |--------------------------------------------------------------------------
            |
            | Contoh:
            |
            | CIANJUR KOTA
            |
            | Jika tidak ada Kota Cianjur tetapi ada Kabupaten Cianjur,
            | gunakan satu-satunya wilayah yang memiliki nama Cianjur.
            */

            if (
                str_ends_with($normalized, ' KOTA') ||
                str_ends_with($normalized, ' KAB')
            ) {

                if ($matches->count() === 1) {
                    return $matches;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TIDAK DITEMUKAN
        |--------------------------------------------------------------------------
        */

        return collect();
    }

    protected function extractSentralBaseName(
        string $value
    ): string {

        $value = trim(
            $this->normalizeSentralDestination($value)
        );

        /*
        * Hilangkan prefix.
        *
        * KAB CIANJUR
        * KOTA CIANJUR
        */
        $value = preg_replace(
            '/^(KAB|KOTA)\s+/i',
            '',
            $value
        );

        /*
        * Hilangkan suffix.
        *
        * CIANJUR KAB
        * CIANJUR KOTA
        * CIANJUR KABUPATEN
        */
        $value = preg_replace(
            '/\s+(KAB|KOTA|KABUPATEN)$/i',
            '',
            $value
        );

        return trim($value);
    }

    protected function reverseSentralPrefix(
        string $value
    ): string {

        $value = trim($value);

        /*
        * SUKABUMI KOTA
        * menjadi
        * KOTA SUKABUMI
        */
        if (preg_match(
            '/^(.+)\s+KOTA$/',
            $value,
            $matches
        )) {

            return 'KOTA ' . trim($matches[1]);
        }

        /*
        * BOGOR KAB
        * menjadi
        * KAB BOGOR
        */
        if (preg_match(
            '/^(.+)\s+KAB$/',
            $value,
            $matches
        )) {

            return 'KAB ' . trim($matches[1]);
        }

        /*
        * KABUPATEN BENTUK LAIN
        */
        if (preg_match(
            '/^(.+)\s+KABUPATEN$/',
            $value,
            $matches
        )) {

            return 'KAB ' . trim($matches[1]);
        }

        return $value;
    }
}