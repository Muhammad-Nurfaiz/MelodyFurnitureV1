<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Models\TemporaryMedia;
use App\Services\Media\TemporaryMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemporaryMediaController extends Controller
{
    public function __construct(
        protected TemporaryMediaService $service
    ) {}
    public function __invoke(
        Request $request,
        TemporaryMediaService $service
    ) {
        $service->cleanup(
            $request->input('media', [])
        );

        return response()->json([
            'success' => true,
        ]);
    }
    /**
     * Upload temporary media
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,mp4,webm,mov',
                'max:51200',
            ],
        ]);

        $media = $this->service->upload(
            $request->file('file')
        );

        return response()->json([
            'success' => true,

            'message' => 'Media berhasil diupload.',

            'data' => [
                'id' => $media->id,
                'url' => asset('storage/'.$media->path),
                'path' => $media->path,
                'filename' => $media->filename,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'media_type' => str_starts_with(
                    $media->mime_type,
                    'video/'
                )
                    ? 'video'
                    : 'image',
            ],
        ]);
    }

    /**
     * Delete temporary image
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Media berhasil dihapus.',
        ]);
    }

    public function cleanup(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        $this->service->cleanup($ids);

        return response()->json([
            'success' => true,
        ]);
    }
}