<?php

namespace App\Console\Commands;

use App\Models\ProductMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class OptimizeProductImages extends Command
{
    protected $signature = 'media:optimize-images
                            {--dry-run : Hanya menampilkan file yang akan diproses tanpa mengubah apa pun}';

    protected $description = 'Mengonversi gambar JPG/PNG product media menjadi WebP tanpa mengubah resolusi';

    protected string $disk = 'public';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $media = ProductMedia::query()
            ->whereIn('media_type', ['image'])
            ->where(function ($query) {
                $query
                    ->where('media_url', 'like', '%.jpg')
                    ->orWhere('media_url', 'like', '%.jpeg')
                    ->orWhere('media_url', 'like', '%.png');
            })
            ->orderBy('created_at')
            ->get();

        if ($media->isEmpty()) {
            $this->info('Tidak ada gambar JPG/PNG yang perlu dioptimasi.');
            return self::SUCCESS;
        }

        $this->info(
            'Ditemukan ' . $media->count() . ' gambar JPG/PNG.'
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('DRY RUN — tidak ada file atau database yang diubah.');
            $this->newLine();
        }

        $manager = new ImageManager(
            new Driver()
        );

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($media as $item) {
            $oldPath = $item->media_url;

            $this->newLine();
            $this->line("Media ID: {$item->id}");
            $this->line("File    : {$oldPath}");

            if (!Storage::disk($this->disk)->exists($oldPath)) {
                $this->warn('  SKIP: file asli tidak ditemukan.');
                $skipped++;
                continue;
            }

            $oldSize = Storage::disk($this->disk)->size($oldPath);

            if ($isDryRun) {
                $this->line(
                    '  Ukuran : ' .
                    $this->formatBytes($oldSize)
                );

                $this->line(
                    '  Status : akan dikonversi menjadi WebP quality 85'
                );

                $processed++;
                continue;
            }

            $directory = pathinfo(
                $oldPath,
                PATHINFO_DIRNAME
            );

            $filename = pathinfo(
                $oldPath,
                PATHINFO_FILENAME
            );

            $newPath = $directory . '/' . $filename . '.webp';

            /*
             * Jika target WebP sudah ada dan berbeda dari file lama,
             * kita tidak menimpanya secara sembarangan.
             */
            if (
                $newPath !== $oldPath &&
                Storage::disk($this->disk)->exists($newPath)
            ) {
                $this->warn(
                    '  SKIP: file WebP tujuan sudah ada.'
                );

                $skipped++;
                continue;
            }

            try {
                $image = $manager->read(
                    Storage::disk($this->disk)->path($oldPath)
                );

                $width = $image->width();
                $height = $image->height();

                $encoded = $image->toWebp(85);

                /*
                 * Simpan ke file sementara terlebih dahulu.
                 * Database belum disentuh.
                 */
                $temporaryPath = $newPath . '.tmp';

                Storage::disk($this->disk)->put(
                    $temporaryPath,
                    $encoded->toString()
                );

                if (
                    !Storage::disk($this->disk)->exists(
                        $temporaryPath
                    )
                ) {
                    throw new \RuntimeException(
                        'File WebP sementara gagal dibuat.'
                    );
                }

                $newSize = Storage::disk($this->disk)->size(
                    $temporaryPath
                );

                if ($newSize <= 0) {
                    throw new \RuntimeException(
                        'File WebP sementara memiliki ukuran 0 byte.'
                    );
                }

                /*
                 * Rename/move file sementara menjadi file WebP final.
                 */
                Storage::disk($this->disk)->move(
                    $temporaryPath,
                    $newPath
                );

                /*
                 * Pastikan file final benar-benar tersedia
                 * sebelum mengubah database.
                 */
                if (
                    !Storage::disk($this->disk)->exists($newPath)
                ) {
                    throw new \RuntimeException(
                        'File WebP final tidak ditemukan.'
                    );
                }

                /*
                 * Update database hanya setelah file WebP berhasil dibuat.
                 */
                DB::transaction(function () use (
                    $item,
                    $newPath
                ) {
                    $item->update([
                        'media_url' => $newPath,
                    ]);
                });

                /*
                 * Setelah database berhasil menunjuk ke WebP,
                 * file lama baru dihapus.
                 */
                Storage::disk($this->disk)->delete($oldPath);

                $this->info(
                    '  OK: ' .
                    $this->formatBytes($oldSize) .
                    ' → ' .
                    $this->formatBytes($newSize) .
                    " | {$width}x{$height}"
                );

                $processed++;
            } catch (Throwable $e) {
                /*
                 * Jika proses gagal, bersihkan file sementara/final
                 * jika memang baru dibuat oleh proses ini.
                 */
                $temporaryPath = ($newPath ?? '') . '.tmp';

                if (
                    $temporaryPath &&
                    Storage::disk($this->disk)->exists($temporaryPath)
                ) {
                    Storage::disk($this->disk)->delete(
                        $temporaryPath
                    );
                }

                $this->error(
                    '  GAGAL: ' . $e->getMessage()
                );

                $failed++;
            }
        }

        $this->newLine();
        $this->info('=== SELESAI ===');
        $this->line("Diproses : {$processed}");
        $this->line("Di-skip  : {$skipped}");
        $this->line("Gagal    : {$failed}");

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}