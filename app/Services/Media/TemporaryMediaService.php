<?php

namespace App\Services\Media;

use App\Models\TemporaryMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class TemporaryMediaService
{
    protected string $disk = 'public';

    public function upload(UploadedFile $file): TemporaryMedia
    {
        return DB::transaction(function () use ($file) {

            $mimeType = strtolower(
                $file->getMimeType() ?? ''
            );

            /*
             * Gambar yang dapat dioptimasi:
             * JPG / JPEG / PNG / WebP
             */
            $isOptimizableImage = in_array(
                $mimeType,
                [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ],
                true
            );

            if ($isOptimizableImage) {
                return $this->storeOptimizedImage($file);
            }

            /*
             * Semua video diproses menjadi MP4
             * menggunakan H.264 + AAC.
             */
            if (str_starts_with($mimeType, 'video/')) {
                return $this->storeOptimizedVideo($file);
            }

            /*
             * SVG dan file lain tetap disimpan asli.
             */
            return $this->storeOriginalFile($file);
        });
    }

    protected function storeOptimizedImage(
        UploadedFile $file
    ): TemporaryMedia {
        $filename = Str::uuid() . '.webp';

        $path = 'temp/' . $filename;

        $manager = new ImageManager(
            new Driver()
        );

        $image = $manager->read(
            $file->getRealPath()
        );

        /*
         * Tidak melakukan resize.
         *
         * Resolusi gambar tetap sama seperti file asli.
         *
         * Hanya mengubah format menjadi WebP
         * dengan quality 85.
         */
        $encoded = $image->toWebp(85);

        Storage::disk($this->disk)->put(
            $path,
            $encoded->toString()
        );

        return TemporaryMedia::create([
            'user_id'    => auth()->id(),
            'disk'       => $this->disk,
            'path'       => $path,
            'filename'   => $filename,
            'mime_type'  => 'image/webp',
            'extension'  => 'webp',
            'size'       => Storage::disk($this->disk)->size($path),
            'expires_at' => now()->addHours(12),
        ]);
    }

    protected function storeOptimizedVideo(
        UploadedFile $file
    ): TemporaryMedia {
        $filename = Str::uuid() . '.mp4';

        $path = 'temp/' . $filename;

        /*
         * File sementara untuk hasil encoding FFmpeg.
         */
        $temporaryOutput = storage_path(
            'app/temp/' . Str::uuid() . '.mp4'
        );

        $inputPath = $file->getRealPath();

        /*
         * Pastikan directory temp tersedia.
         */
        $temporaryDirectory = dirname(
            $temporaryOutput
        );

        if (!is_dir($temporaryDirectory)) {
            mkdir(
                $temporaryDirectory,
                0755,
                true
            );
        }

        /*
         * H.264 CRF 28:
         *
         * - tidak mengubah resolusi
         * - tidak mengubah FPS secara eksplisit
         * - ukuran jauh lebih kecil
         * - kualitas visual masih aman berdasarkan
         *   pengujian video existing
         *
         * Audio:
         * AAC 64 kbps.
         *
         * +faststart:
         * memindahkan metadata MP4 ke awal file.
         */
        $ffmpegBinary = config('media.ffmpeg', 'ffmpeg');

        $process = new Process([
            $ffmpegBinary,
            '-y',
            '-i',
            $inputPath,

            '-c:v',
            'libx264',

            '-preset',
            'medium',

            '-crf',
            '28',

            '-c:a',
            'aac',

            '-b:a',
            '64k',

            '-movflags',
            '+faststart',

            $temporaryOutput,
        ]);

        /*
         * Timeout 10 menit.
         *
         * Video upload biasanya jauh lebih kecil,
         * tetapi encoding memang membutuhkan waktu.
         */
        $process->setTimeout(600);

        $process->run();

        if ($process->getExitCode() !== 0) {
            if (file_exists($temporaryOutput)) {
                @unlink($temporaryOutput);
            }

            throw new \RuntimeException(
                'Gagal mengoptimasi video dengan FFmpeg: ' .
                trim($process->getErrorOutput())
            );
        }

        /*
         * Pastikan output benar-benar dibuat.
         */
        if (
            !file_exists($temporaryOutput) ||
            filesize($temporaryOutput) <= 0
        ) {
            if (file_exists($temporaryOutput)) {
                @unlink($temporaryOutput);
            }

            throw new \RuntimeException(
                'FFmpeg tidak menghasilkan file video yang valid.'
            );
        }

        $originalSize = $file->getSize();
        $optimizedSize = filesize($temporaryOutput);

        if (
            $originalSize !== false &&
            $optimizedSize !== false &&
            $optimizedSize >= $originalSize
        ) {
            @unlink($temporaryOutput);

            return $this->storeOriginalFile($file);
        }

        /*
         * Simpan hasil ke public disk.
         */
        $stream = fopen(
            $temporaryOutput,
            'rb'
        );

        try {
            Storage::disk($this->disk)->put(
                $path,
                $stream
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($temporaryOutput);
        }

        /*
         * Pastikan file final benar-benar ada.
         */
        if (
            !Storage::disk($this->disk)->exists($path)
        ) {
            throw new \RuntimeException(
                'File video hasil optimasi gagal disimpan.'
            );
        }

        $size = Storage::disk($this->disk)->size(
            $path
        );

        if ($size <= 0) {
            Storage::disk($this->disk)->delete($path);

            throw new \RuntimeException(
                'File video hasil optimasi memiliki ukuran 0 byte.'
            );
        }

        return TemporaryMedia::create([
            'user_id'    => auth()->id(),
            'disk'       => $this->disk,
            'path'       => $path,
            'filename'   => $filename,
            'mime_type'  => 'video/mp4',
            'extension'  => 'mp4',
            'size'       => $size,
            'expires_at' => now()->addHours(12),
        ]);
    }

    protected function storeOriginalFile(
        UploadedFile $file
    ): TemporaryMedia {
        $filename = Str::uuid() . '.' . $file->extension();

        $path = $file->storeAs(
            'temp',
            $filename,
            $this->disk
        );

        return TemporaryMedia::create([
            'user_id'    => auth()->id(),
            'disk'       => $this->disk,
            'path'       => $path,
            'filename'   => $filename,
            'mime_type'  => $file->getMimeType(),
            'extension'  => $file->extension(),
            'size'       => $file->getSize(),
            'expires_at' => now()->addHours(12),
        ]);
    }

    public function delete(string $uuid): void
    {
        $media = $this->find($uuid);

        Storage::disk($media->disk)
            ->delete($media->path);

        $media->delete();
    }

    public function find(string $uuid): TemporaryMedia
    {
        return TemporaryMedia::query()
            ->whereKey($uuid)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    public function moveTo(
        string $uuid,
        string $directory
    ): string {
        $media = $this->find($uuid);

        $extension = pathinfo(
            $media->filename,
            PATHINFO_EXTENSION
        );

        $newFilename = Str::uuid() . '.' . $extension;

        $newPath = $directory . '/' . $newFilename;

        Storage::disk($media->disk)->move(
            $media->path,
            $newPath
        );

        $media->delete();

        return $newPath;
    }

    public function moveMany(
        array $uuids,
        string $directory
    ): array {
        $result = [];

        foreach ($uuids as $uuid) {
            $result[$uuid] = $this->moveTo(
                $uuid,
                $directory
            );
        }

        return $result;
    }

    public function exists(string $uuid): bool
    {
        return TemporaryMedia::query()
            ->whereKey($uuid)
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function getMany(array $uuids)
    {
        return TemporaryMedia::query()
            ->whereIn('id', $uuids)
            ->where('user_id', auth()->id())
            ->get();
    }

    public function purgeExpired(): void
    {
        TemporaryMedia::query()
            ->where('expires_at', '<', now())
            ->each(function ($media) {
                Storage::disk($media->disk)
                    ->delete($media->path);

                $media->delete();
            });
    }

    public function cleanupExpired(): int
    {
        $expiredMedia = TemporaryMedia::where(
            'expires_at',
            '<=',
            now()
        )->get();

        foreach ($expiredMedia as $media) {
            Storage::disk($media->disk)
                ->delete($media->path);

            $media->delete();
        }

        return $expiredMedia->count();
    }

    public function cleanup(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $media = TemporaryMedia::query()
            ->whereIn('id', $ids)
            ->where('user_id', auth()->id())
            ->get();

        foreach ($media as $item) {
            Storage::disk($item->disk)
                ->delete($item->path);

            $item->delete();
        }
    }
}