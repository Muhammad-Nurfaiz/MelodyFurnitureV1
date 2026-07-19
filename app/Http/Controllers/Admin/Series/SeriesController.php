<?php

namespace App\Http\Controllers\Admin\Series;

use App\Models\Series;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminController;
use App\Services\Series\SeriesService;
use App\Http\Requests\Admin\Series\StoreSeriesRequest;
use App\Http\Requests\Admin\Series\UpdateSeriesRequest;

class SeriesController extends AdminController
{
    public function __construct(
        protected SeriesService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->allow('viewAny', Series::class);

        $series = $this->service->paginate(
            search: $request->search
        );

        return view(
            'admin.modules.series.index',
            compact('series')
        );
    }

    /**
     * Store a newly created resource.
     */
    public function store(
        StoreSeriesRequest $request
    )
    {
        $this->allow('create', Series::class);

        $this->service->store(
            $request->validated()
        );

        return $this->success(
            'admin.series.index',
            'Series berhasil ditambahkan.'
        );
    }

    /**
     * Update the specified resource.
     */
    public function update(
        UpdateSeriesRequest $request,
        Series $series
    )
    {
        $this->allow('update', $series);

        $this->service->update(
            $series,
            $request->validated()
        );

        return $this->success(
            'admin.series.index',
            'Series berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(
        Series $series
    )
    {
        $this->allow('delete', $series);

        $this->service->destroy($series);

        return $this->successBack(
            'Series berhasil dihapus.'
        );
    }
}