<?php

namespace App\Http\Controllers\Admin\Category;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\Category\CategoryService;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;

class CategoryController extends AdminController
{
    public function __construct(

        protected CategoryService $service

    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->allow('viewAny', Category::class);

        $categories = $this->service->paginate($request);

        return view(
            'admin.modules.category.index',
            compact('categories')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreCategoryRequest $request
    )
    {
        $this->service->store(

            $request->validated()

        );

        return $this->success(

            'admin.categories.index',

            'Kategori berhasil ditambahkan.'

        );
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCategoryRequest $request,

        Category $category

    )
    {
        $this->service->update(

            $category,

            $request->validated()

        );

        return $this->success(

            'admin.categories.index',

            'Kategori berhasil diperbarui.'

        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Category $category
    )
    {
        $this->allow('delete', $category);

        $this->service->destroy($category);

        return $this->successBack(
            'Kategori berhasil dihapus.'
        );
    }
}
