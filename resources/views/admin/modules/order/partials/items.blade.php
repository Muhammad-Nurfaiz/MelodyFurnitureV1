<x-admin.card>

    <x-admin.card-header
        title="Order Items"
        description="Daftar produk yang dibeli customer."
    >
    </x-admin.card-header>

    <x-admin.card-body>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="border-b bg-slate-50">

                    <tr>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">
                            Produk
                        </th>

                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase">
                            Qty
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">
                            Harga
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">
                            Subtotal
                        </th>

                    </tr>

                </thead>

                <tbody>

                @foreach($order->items as $item)

                    <tr class="border-b">

                        {{-- Product --}}
                        <td class="px-4 py-4">

                            <div class="flex gap-4">

                                <img
                                    src="{{ $item->product_image }}"
                                    class="w-16 h-16 rounded-lg object-cover border"
                                >

                                <div>

                                    <p class="font-semibold">
                                        {{ $item->product_name }}
                                    </p>

                                    <p class="text-sm text-slate-500">
                                        {{ $item->product_slug }}
                                    </p>

                                </div>

                            </div>

                        </td>

                        {{-- Qty --}}
                        <td class="px-4 py-4 text-center">

                            {{ $item->quantity }}

                        </td>

                        {{-- Price --}}
                        <td class="px-4 py-4 text-right">

                            <x-admin.price
                                :original="$item->unit_price"
                            />

                        </td>

                        {{-- Subtotal --}}
                        <td class="px-4 py-4 text-right font-semibold">

                            <x-admin.price
                                :original="$item->subtotal"
                            />

                        </td>

                    </tr>

                @endforeach

                </tbody>

                <tfoot>

                    <tr>

                        <td colspan="3"
                            class="text-right px-4 py-4 font-semibold">

                            Total Produk

                        </td>

                        <td class="text-right px-4 py-4 font-bold">

                            <x-admin.price
                                :original="$order->total_product_price"
                            />

                        </td>

                    </tr>

                    <tr>

                        <td colspan="3"
                            class="text-right px-4 py-2">

                            Voucher

                        </td>

                        <td class="text-right px-4 py-2">

                            -

                            <x-admin.price
                                :original="$order->voucher_discount_amount"
                            />

                        </td>

                    </tr>

                    <tr>

                        <td colspan="3"
                            class="text-right px-4 py-2">

                            Ongkir

                        </td>

                        <td class="text-right px-4 py-2">

                            <x-admin.price
                                :original="$order->shipping_fee"
                            />

                        </td>

                    </tr>

                    <tr class="border-t">

                        <td colspan="3"
                            class="text-right px-4 py-4 text-lg font-bold">

                            Grand Total

                        </td>

                        <td class="text-right px-4 py-4 text-lg font-bold">

                            <x-admin.price
                                :original="$order->total_payment"
                            />

                        </td>

                    </tr>

                </tfoot>

            </table>

        </div>

    </x-admin.card-body>

</x-admin.card>