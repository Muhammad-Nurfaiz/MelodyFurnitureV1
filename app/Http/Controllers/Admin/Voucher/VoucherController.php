<?php

namespace App\Http\Controllers\Admin\Voucher;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\Voucher\VoucherAdminService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherAdminService $adminService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $vouchers = $this->adminService->list($request);
        $stats = $this->adminService->stats();

        return view(
            'admin.modules.voucher.index',
            compact('vouchers', 'stats')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view(
            'admin.modules.voucher.create'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $voucher = $this->adminService->create(
            $request->all()
        );

        return redirect()
            ->route('admin.vouchers.show', $voucher)
            ->with(
                'success',
                'Voucher berhasil dibuat.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(string $id)
    {
        $voucher = $this->adminService->show($id);

        return view(
            'admin.modules.voucher.show',
            compact('voucher')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(string $id)
    {
        $voucher = $this->adminService->show($id);

        return view(
            'admin.modules.voucher.create',
            compact('voucher')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        string $id
    ): RedirectResponse {

        $voucher = $this->adminService->update(
            $id,
            $request->all()
        );

        return redirect()
            ->route('admin.vouchers.show', $voucher)
            ->with(
                'success',
                'Voucher berhasil diperbarui.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(string $id)
    {
        $voucher = $this->adminService->show($id);

        $this->adminService->delete($voucher);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Active
    |--------------------------------------------------------------------------
    */

    public function toggleActive(string $id)
    {
        $voucher = $this->adminService->show($id);

        $this->adminService->toggleActive($voucher);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Status voucher berhasil diubah.');
    }
}