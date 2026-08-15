<?php

namespace App\Http\Controllers\Admin\Shipping;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Shipping\UpdateShippingRateRequest;
use App\Models\ShippingCourier;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use MadeByClowd\Nusantara\Models\Province;

class ShippingRateController extends Controller
{
    /**
     * Display shipping rates.
     */
    public function index(Request $request)
    {
        $couriers = ShippingCourier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $provinces = Province::query()
            ->orderBy('name')
            ->get();

        $rates = ShippingRate::query()
            ->with([
                'courier',
                'regency.province',
            ])
            ->where('is_active', true)

            /*
            |----------------------------------------------------------------------
            | Courier Filter
            |----------------------------------------------------------------------
            */

            ->when(
                $request->filled('courier'),
                fn ($query) =>
                    $query->where(
                        'courier_id',
                        $request->courier
                    )
            )

            /*
            |----------------------------------------------------------------------
            | Province Filter
            |----------------------------------------------------------------------
            */

            ->when(
                $request->filled('province'),
                function ($query) use ($request) {

                    $query->whereHas(
                        'regency',
                        function ($q) use ($request) {

                            $q->where(
                                'province_id',
                                $request->province
                            );
                        }
                    );
                }
            )

            /*
            |----------------------------------------------------------------------
            | Regency Search
            |----------------------------------------------------------------------
            */

            ->when(
                $request->filled('search'),
                function ($query) use ($request) {

                    $search = $request->search;

                    $query->whereHas(
                        'regency',
                        function ($q) use ($search) {

                            $q->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
                }
            )

            /*
            |----------------------------------------------------------------------
            | Sorting
            |----------------------------------------------------------------------
            */

            ->join(
                'regencies',
                'shipping_rates.regency_id',
                '=',
                'regencies.id'
            )

            ->select('shipping_rates.*')

            ->orderBy('regencies.name')

            ->paginate(15)

            ->withQueryString();

        return view('admin.modules.shipping.index', [
            'rates' => $rates,
            'couriers' => $couriers,
            'provinces' => $provinces,
        ]);
    }

    /**
     * Edit shipping rate.
     */
    public function edit(ShippingRate $shippingRate)
    {
        $shippingRate->load([
            'courier',
            'regency.province',
        ]);

        return view('admin.modules.shipping.edit', [
            'rate' => $shippingRate,
        ]);
    }

    /**
     * Update shipping rate.
     */
    public function update(
        UpdateShippingRateRequest $request,
        ShippingRate $shippingRate
    ) {

        $data = $request->validated();

        /*
        |----------------------------------------------------------------------
        | Normalize Rate Fields
        |----------------------------------------------------------------------
        |
        | Hanya field yang sesuai dengan rate_type yang digunakan.
        |
        */

        if ($shippingRate->rate_type === 'per_kg') {

            $shippingRate->update([
                'price_per_kg' => $data['price_per_kg'],
                'first_price' => null,
                'additional_price_per_kg' => null,
            ]);

        } else {

            $shippingRate->update([
                'price_per_kg' => null,
                'first_price' => $data['first_price'],
                'additional_price_per_kg' =>
                    $data['additional_price_per_kg'],
            ]);
        }

        return redirect()
            ->route('admin.shipping-rates.index')
            ->with(
                'success',
                'Tarif shipping berhasil diperbarui.'
            );
    }
}