<?php

namespace App\Console\Commands;

use App\Models\ProductMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class OptimizeProductVideos extends Command
{
    protected $signature = 'media:optimize-videos
                            {--dry-run : Hanya menampilkan video yang akan diproses tanpa mengubah apa pun}';

    protected $description = 'Mengoptimasi video product media menjadi H.264 CRF 28 tanpa mengubah resolusi';

    protected string $disk = 'public';

    protected int $crf = 28;

    protected string $audioBitrate = '64k';

    protected string $preset = 'medium';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $media = ProductMedia::query()
            ->where('media_type', 'video')
            ->orderBy('created_at')
            ->get();

        if ($media->isEmpty()) {
            $this->info(
                'Tidak ada video product media yang perlu dioptimasi.'
            );

            return self::SUCCESS;
        }

        $this->info(
            'Ditemukan ' . $media->count() . ' video.'
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn(
                'DRY RUN — tidak ada file atau database yang diubah.'
            );

            $this->newLine();
        }

        /*
         * Pastikan FFmpeg tersedia.
         */
        $ffmpegBinary = config('media.ffmpeg', 'ffmpeg');
        $ffmpegCheck = new Process([
            $ffmpegBinary,
            '-version',
        ]);

        $ffmpegCheck->setTimeout(30);
        $ffmpegCheck->run();

        if ($ffmpegCheck->getExitCode() !== 0) {
            $this->error(
                'FFmpeg tidak ditemukan atau tidak dapat dijalankan.'
            );

            return self::FAILURE;
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($media as $item) {
            $oldPath = $item->media_url;

            $this->line("Media ID: {$item->id}");
            $this->line("File    : {$oldPath}");

            if (!Storage::disk($this->disk)->exists($oldPath)) {
                $this->warn(
                    '  SKIP: file video tidak ditemukan.'
                );

                $skipped++;

                $this->newLine();

                continue;
            }

            $oldSize = Storage::disk($this->disk)->size(
                $oldPath
            );

            $this->line(
                '  Ukuran : ' .
                $this->formatBytes($oldSize)
            );

            if ($isDryRun) {
                $this->line(
                    '  Status : akan dikonversi dengan H.264 CRF 28'
                );

                $this->line(
                    '  Audio  : AAC 64 kbps'
                );

                $this->line(
                    '  Faststart: ya'
                );

                $processed++;

                $this->newLine();

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

            $newPath = $directory . '/' . $filename . '.mp4';

            /*
             * Gunakan nama sementara agar file asli tidak tersentuh
             * sebelum hasil encoding benar-benar valid.
             */
            $temporaryPath =
                $directory . '/' .
                $filename .
                '.optimized.tmp.mp4';

            $oldAbsolutePath = Storage::disk(
                $this->disk
            )->path($oldPath);

            $temporaryAbsolutePath = Storage::disk(
                $this->disk
            )->path($temporaryPath);

            try {
                /*
                 * Jika file tujuan sudah ada dan bukan file asli,
                 * jangan menimpanya secara otomatis.
                 */
                if (
                    $newPath !== $oldPath &&
                    Storage::disk($this->disk)->exists($newPath)
                ) {
                    $this->warn(
                        '  SKIP: file tujuan sudah ada.'
                    );

                    $skipped++;

                    $this->newLine();

                    continue;
                }

                /*
                 * Encoding:
                 *
                 * - H.264
                 * - CRF 28
                 * - preset medium
                 * - audio AAC 64 kbps
                 * - resolusi dan FPS mengikuti sumber
                 * - faststart untuk penggunaan web
                 */
                $process = new Process([
                    $ffmpegBinary,
                    '-y',
                    '-i',
                    $oldAbsolutePath,

                    '-c:v',
                    'libx264',
                    '-preset',
                    $this->preset,
                    '-crf',
                    (string) $this->crf,

                    '-c:a',
                    'aac',
                    '-b:a',
                    $this->audioBitrate,

                    '-movflags',
                    '+faststart',

                    $temporaryAbsolutePath,
                ]);

                $process->setTimeout(3600);
                $process->run();

                if ($process->getExitCode() !== 0) {
                    throw new \RuntimeException(
                        trim($process->getErrorOutput())
                    );
                }

                /*
                 * Pastikan hasil encoding benar-benar ada.
                 */
                if (
                    !Storage::disk($this->disk)
                        ->exists($temporaryPath)
                ) {
                    throw new \RuntimeException(
                        'File hasil optimasi tidak ditemukan.'
                    );
                }

                $newSize = Storage::disk($this->disk)->size(
                    $temporaryPath
                );

                if ($newSize <= 0) {
                    throw new \RuntimeException(
                        'File hasil optimasi memiliki ukuran 0 byte.'
                    );
                }

                if ($newSize >= $oldSize) {
                    Storage::disk($this->disk)->delete($temporaryPath);

                    $this->warn(
                        '  SKIP: hasil optimasi tidak lebih kecil dari file asli.'
                    );

                    $skipped++;

                    $this->newLine();

                    continue;
                }

                /*
                 * Validasi hasil menggunakan ffprobe.
                 */
                $probe = new Process([
                    'ffprobe',
                    '-v',
                    'error',
                    '-select_streams',
                    'v:0',
                    '-show_entries',
                    'stream=codec_name,width,height',
                    '-of',
                    'default=noprint_wrappers=1',
                    $temporaryAbsolutePath,
                ]);

                $probe->setTimeout(60);
                $probe->run();

                if ($probe->getExitCode() !== 0) {
                    throw new \RuntimeException(
                        'FFprobe gagal memvalidasi file hasil.'
                    );
                }

                $probeOutput = $probe->getOutput();

                if (
                    !str_contains(
                        $probeOutput,
                        'codec_name=h264'
                    )
                ) {
                    throw new \RuntimeException(
                        'File hasil bukan video H.264.'
                    );
                }

                if (
                    !preg_match(
                        '/width=(\d+)/',
                        $probeOutput,
                        $widthMatch
                    ) ||
                    !preg_match(
                        '/height=(\d+)/',
                        $probeOutput,
                        $heightMatch
                    )
                ) {
                    throw new \RuntimeException(
                        'Resolusi video hasil tidak dapat dibaca.'
                    );
                }

                /*
                 * Pastikan resolusi tidak berubah.
                 */
                $originalProbe = new Process([
                    'ffprobe',
                    '-v',
                    'error',
                    '-select_streams',
                    'v:0',
                    '-show_entries',
                    'stream=width,height',
                    '-of',
                    'default=noprint_wrappers=1',
                    $oldAbsolutePath,
                ]);

                $originalProbe->setTimeout(60);
                $originalProbe->run();

                if ($originalProbe->getExitCode() !== 0) {
                    throw new \RuntimeException(
                        'FFprobe gagal membaca resolusi video asli.'
                    );
                }

                $originalOutput = $originalProbe->getOutput();

                preg_match(
                    '/width=(\d+)/',
                    $originalOutput,
                    $originalWidthMatch
                );

                preg_match(
                    '/height=(\d+)/',
                    $originalOutput,
                    $originalHeightMatch
                );

                if (
                    empty($originalWidthMatch[1]) ||
                    empty($originalHeightMatch[1])
                ) {
                    throw new \RuntimeException(
                        'Resolusi video asli tidak dapat dibaca.'
                    );
                }

                $originalWidth = (int) $originalWidthMatch[1];
                $originalHeight = (int) $originalHeightMatch[1];

                $newWidth = (int) $widthMatch[1];
                $newHeight = (int) $heightMatch[1];

                if (
                    $originalWidth !== $newWidth ||
                    $originalHeight !== $newHeight
                ) {
                    throw new \RuntimeException(
                        "Resolusi berubah dari {$originalWidth}x{$originalHeight} " .
                        "menjadi {$newWidth}x{$newHeight}."
                    );
                }

                /*
                 * File hasil sekarang dianggap valid.
                 *
                 * Jika nama file baru berbeda dari file asli,
                 * pindahkan hasil sementara menjadi file final.
                 */
                if ($temporaryPath !== $newPath) {
                    Storage::disk($this->disk)->move(
                        $temporaryPath,
                        $newPath
                    );
                }

                if (
                    !Storage::disk($this->disk)->exists($newPath)
                ) {
                    throw new \RuntimeException(
                        'File final hasil optimasi tidak ditemukan.'
                    );
                }

                /*
                 * Update database hanya setelah file final valid.
                 *
                 * Jika thumbnail_url masih menunjuk ke file lama,
                 * ikut diarahkan ke file baru agar tidak menjadi
                 * broken URL.
                 */
                DB::transaction(function () use (
                    $item,
                    $oldPath,
                    $newPath
                ) {
                    $updates = [
                        'media_url' => $newPath,
                    ];

                    if ($item->thumbnail_url === $oldPath) {
                        $updates['thumbnail_url'] = $newPath;
                    }

                    $item->update($updates);
                });

                /*
                 * Database sudah menunjuk ke file baru.
                 * Sekarang file lama aman untuk dihapus.
                 */
                if ($oldPath !== $newPath) {
                    Storage::disk($this->disk)->delete(
                        $oldPath
                    );
                }

                $saving = $oldSize > 0
                    ? round(
                        (1 - ($newSize / $oldSize)) * 100,
                        2
                    )
                    : 0;

                $this->info(
                    '  OK: ' .
                    $this->formatBytes($oldSize) .
                    ' → ' .
                    $this->formatBytes($newSize) .
                    " | hemat {$saving}% | {$newWidth}x{$newHeight}"
                );

                $processed++;
            } catch (Throwable $e) {
                /*
                 * Jika proses gagal, jangan menyentuh file asli.
                 */
                if (
                    Storage::disk($this->disk)->exists(
                        $temporaryPath
                    )
                ) {
                    Storage::disk($this->disk)->delete(
                        $temporaryPath
                    );
                }

                /*
                 * Jika file final sempat dibuat tetapi database
                 * belum berhasil diperbarui, hapus file tersebut
                 * agar tidak meninggalkan file yatim.
                 */
                if (
                    $newPath !== $oldPath &&
                    Storage::disk($this->disk)->exists($newPath)
                ) {
                    Storage::disk($this->disk)->delete($newPath);
                }

                $this->error(
                    '  GAGAL: ' . $e->getMessage()
                );

                $failed++;
            }

            $this->newLine();
        }

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

