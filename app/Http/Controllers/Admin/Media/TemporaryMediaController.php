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

    public function cleanup(Request $request)
    {
        $ids = $request->input('ids', []);
        dd(
            $ids,
            TemporaryMedia::whereIn('id', $ids)->count()
        );
        if (empty($ids)) {
            return response()->json(['success' => true]);
        }

        $media = TemporaryMedia::whereIn('id', $ids)->get();

        foreach ($media as $item) {

            Storage::disk('public')->delete($item->path);

            $item->delete();
        }

        return response()->json([
            'success' => true,
        ]);
    }
}