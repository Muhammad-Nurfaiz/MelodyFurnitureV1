<?php

namespace App\Services\Media;

use App\Models\TemporaryMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TemporaryMediaService
{
    protected string $disk = 'public';

    /**
     * Upload file ke temporary storage
     */
    public function upload(UploadedFile $file): TemporaryMedia
    {
        return DB::transaction(function () use ($file) {

            $filename = Str::uuid().'.'.$file->extension();

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

                'expires_at' => now()->addMinutes(3),

            ]);
        });
    }

    /**
     * Hapus temporary
     */
    public function delete(string $uuid): void
    {
        $media = $this->find($uuid);

        Storage::disk($media->disk)
            ->delete($media->path);

        $media->delete();
    }

    /**
     * Ambil Temporary
     */
    public function find(string $uuid): TemporaryMedia
    {
        return TemporaryMedia::query()
            ->whereKey($uuid)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /**
     * Pindahkan ke folder permanen
     */
    public function moveTo(
        string $uuid,
        string $directory
    ): string {

        $media = $this->find($uuid);

        $extension = pathinfo(
            $media->filename,
            PATHINFO_EXTENSION
        );

        $newFilename = Str::uuid().'.'.$extension;

        $newPath = $directory.'/'.$newFilename;

        Storage::disk($media->disk)->move(
            $media->path,
            $newPath
        );

        $media->delete();

        return $newPath;
    }

    /**
     * Pindahkan banyak temporary media
     *
     * @return array<string,string>
     * key   = temporary uuid
     * value = path permanen
     */
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

            Storage::disk($media->disk)->delete($media->path);

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