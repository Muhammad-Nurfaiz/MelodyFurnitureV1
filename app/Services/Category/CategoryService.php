<?php

namespace App\Services\Category;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * List kategori.
     */
    public function paginate(
        Request $request,
        ?int $perPage = null
    ): LengthAwarePaginator {

        $perPage ??= config('admin.pagination', 5);

        return Category::query()

            ->when(
                $request->filled('search'),
                function ($query) use ($request) {

                    $query->where(
                        'name',
                        'like',
                        '%' . $request->search . '%'
                    );

                }
            )

            ->latest()

            ->paginate($perPage)

            ->withQueryString();

    }

    /**
     * Simpan kategori.
     */
    public function store(
        array $data
    ): Category {

        $data['slug'] = Str::slug($data['name']);

        return Category::create($data);

    }

    /**
     * Update kategori.
     */
    public function update(
        Category $category,
        array $data
    ): bool {

        $data['slug'] = Str::slug($data['name']);

        return $category->update($data);

    }

    /**
     * Hapus kategori.
     */
    public function destroy(
        Category $category
    ): bool {

        return $category->delete();

    }

    private function generateSlug(
        string $name
    ): string {
        return Str::slug($name);
    }
}