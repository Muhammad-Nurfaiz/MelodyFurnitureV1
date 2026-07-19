<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Series;
use App\Services\Product\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::query()
            ->with([
                'category',
                'series',
                'thumbnail',
            ])
            ->search($request->search)
            ->category($request->category)
            ->series($request->series)
            ->when(
                $request->boolean('sale'),
                fn ($query) => $query->sale()
            )
            ->when(
                $request->boolean('ready_stock'),
                fn ($query) => $query->where('ready_stock', '>', 0)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Product::count(),
            'sale' => Product::sale()->count(),
            'stock' => Product::where('ready_stock', '>', 0)->count(),
            'categories' => Category::count(),
        ];

        return view('admin.modules.product.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
            'series'     => Series::orderBy('name')->get(),
            'stats'      => $stats,
        ]);
    }

    /**
     * Create Form
     */
    public function create()
    {
        return view('admin.modules.product.create', [
            'categories' => Category::orderBy('name')->get(),
            'series'     => Series::orderBy('name')->get(),
        ]);
    }

    /**
     * Store Product
     */
    public function store(StoreProductRequest $request)
    {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('admin.products.index')
            ->with(
                'success',
                'Produk berhasil ditambahkan.'
            );
    }

    /**
     * Edit Form
     */
    public function edit(Product $product)
    {
        $product->load([
            'category',
            'series',
            'specification',
            'media' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('admin.modules.product.edit', [
            'product'    => $product,
            'categories' => Category::orderBy('name')->get(),
            'series'     => Series::orderBy('name')->get(),
        ]);
    }

    /**
     * Update Product
     */
    public function update(
        UpdateProductRequest $request,
        Product $product
    ) {
        $this->service->update(
            $product,
            $request->validated()
        );

        return redirect()
            ->route('admin.products.index')
            ->with(
                'success',
                'Produk berhasil diperbarui.'
            );
    }

    /**
     * Delete Product
     */
    public function destroy(Product $product)
    {
        $this->service->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with(
                'success',
                'Produk berhasil dihapus.'
            );
    }
}