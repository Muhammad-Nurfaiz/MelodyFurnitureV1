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
use Illuminate\Support\Facades\Storage;

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
                'variants',
            ])
            ->search($request->search)
            ->category($request->category)
            ->series($request->series)

            // Filter Sale
            ->when(
                $request->input('sale') === 'promo',
                fn ($query) => $query->where('is_sale', true)
            )
            ->when(
                $request->input('sale') === 'normal',
                fn ($query) => $query->where('is_sale', false)
            )

            // Filter Stock
            ->when(
                $request->input('stock') === 'ready',
                function ($query) {
                    $query->where(function ($q) {
                        // Produk tanpa varian
                        $q->where(function ($subQuery) {
                            $subQuery
                                ->whereDoesntHave('variants')
                                ->where('ready_stock', '>', 0);
                        })

                        // Produk dengan varian aktif yang memiliki stok
                        ->orWhereHas('variants', function ($variantQuery) {
                            $variantQuery
                                ->where('is_active', true)
                                ->where('ready_stock', '>', 0);
                        });
                    });
                }
            )
            ->when(
                $request->input('stock') === 'kosong',
                function ($query) {
                    $query->where(function ($q) {
                        // Produk tanpa varian dan stok habis
                        $q->where(function ($subQuery) {
                            $subQuery
                                ->whereDoesntHave('variants')
                                ->where('ready_stock', '<=', 0);
                        })

                        // Produk dengan varian, tetapi tidak ada varian aktif yang ready
                        ->orWhere(function ($subQuery) {
                            $subQuery
                                ->whereHas('variants')
                                ->whereDoesntHave('variants', function ($variantQuery) {
                                    $variantQuery
                                        ->where('is_active', true)
                                        ->where('ready_stock', '>', 0);
                                });
                        });
                    });
                }
            )

            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Product::count(),

            'sale' => Product::sale()->count(),

            'stock' => Product::query()
                ->where(function ($query) {
                    $query->where('ready_stock', '>', 0)
                        ->orWhereHas('variants', function ($variantQuery) {
                            $variantQuery
                                ->where('is_active', true)
                                ->where('ready_stock', '>', 0);
                        });
                })
                ->count(),

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
        Storage::disk('public')->deleteDirectory(
            "products/{$product->id}"
        );
        return redirect()
            ->route('admin.products.index')
            ->with(
                'success',
                'Produk berhasil dihapus.'
            );
    }
}