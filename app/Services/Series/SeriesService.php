<?php

namespace App\Services\Series;

use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SeriesService
{
    /**
     * List series.
     */
    public function paginate(
        ?string $search = null,
        ?int $perPage = null
    ): LengthAwarePaginator {

        $perPage ??= config('admin.pagination', 10);

        return Series::query()
            ->when(
                filled($search),
                fn($query) =>
                    $query->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Simpan series.
     */
    public function store(
        array $data
    ): Series {

        $data['slug'] = $this->generateSlug($data['name']);

        return Series::create($data);
    }

    /**
     * Update series.
     */
    public function update(
        Series $series,
        array $data
    ): bool {

        $data['slug'] = $this->generateSlug($data['name']);;

        return $series->update($data);
    }

    /**
     * Hapus series.
     */
    public function destroy(
        Series $series
    ): bool {

        return $series->delete();
    }

    private function generateSlug(string $name): string
    {
        return Str::slug($name);
    }
}