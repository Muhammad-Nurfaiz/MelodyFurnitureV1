<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Services\Media\TemporaryMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemporaryMediaController extends Controller
{
    public function __construct(
        protected TemporaryMediaService $service
    ) {}

    /**
     * Upload temporary image
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
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
}