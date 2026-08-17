<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Invoice {{ $order->order_number }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
        }

        .container {
            width: 100%;
        }

        .header {
            width: 100%;
            margin-bottom: 30px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .company {
            font-size: 20px;
            font-weight: bold;
        }

        .invoice-title {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
        }

        .invoice-number {
            text-align: right;
            margin-top: 5px;
            color: #6b7280;
        }

        .section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items th {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            padding: 9px;
            text-align: left;
            font-size: 11px;
        }

        .items td {
            border-bottom: 1px solid #e5e7eb;
            padding: 9px;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            width: 45%;
            margin-left: auto;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .summary td {
            padding: 5px 0;
        }

        .summary .total td {
            border-top: 2px solid #111827;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 10px;
        }

    </style>

</head>

<body>

<div class="container">

    {{-- Header --}}

    <div class="header">

        <table class="header-table">

            <tr>

                <td>

                    <div class="company">
                        Melody Furniture
                    </div>

                    <div style="margin-top: 5px; color: #6b7280;">
                        Invoice Pembelian
                    </div>

                </td>

                <td>

                    <div class="invoice-title">
                        INVOICE
                    </div>

                    <div class="invoice-number">
                        {{ $order->order_number }}
                    </div>

                    <div class="invoice-number">
                        {{ $order->created_at?->format('d M Y') }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- Customer --}}

    <div class="section">

        <div class="section-title">
            Customer
        </div>

        <table class="info-table">

            <tr>
                <td style="width: 120px;">
                    Nama
                </td>

                <td>
                    {{ $order->customer_name ?? '-' }}
                </td>
            </tr>

            <tr>
                <td>
                    Email
                </td>

                <td>
                    {{ $order->customer_email ?? '-' }}
                </td>
            </tr>

            <tr>
                <td>
                    Telepon
                </td>

                <td>
                    {{ $order->customer_phone ?? '-' }}
                </td>
            </tr>

        </table>

    </div>


    {{-- Shipping --}}

    <div class="section">

        <div class="section-title">
            Alamat Pengiriman
        </div>

        @php
            $address = $order->shipping_address ?? [];
        @endphp

        <div>

            {{ $address['recipient_name'] ?? $order->customer_name ?? '-' }}

        </div>

        <div style="margin-top: 4px;">

            {{ $address['address'] ?? '-' }}

        </div>

        <div style="margin-top: 4px;">

            {{ $address['city'] ?? '-' }},
            {{ $address['province'] ?? '-' }}

            {{ $address['postal_code'] ?? '' }}

        </div>

    </div>


    {{-- Items --}}

    <div class="section">

        <div class="section-title">
            Detail Pesanan
        </div>

        <table class="items">

            <thead>

                <tr>

                    <th>
                        Produk
                    </th>

                    <th style="width: 70px;">
                        Qty
                    </th>

                    <th style="width: 120px;" class="text-right">
                        Harga
                    </th>

                    <th style="width: 130px;" class="text-right">
                        Subtotal
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach($order->items as $item)

                    <tr>

                        <td>

                            <strong>
                                {{ $item->product_name }}
                            </strong>

                            @if($item->product_sku)

                                <div style="margin-top: 3px; color: #6b7280;">
                                    SKU: {{ $item->product_sku }}
                                </div>

                            @endif

                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td class="text-right">

                            Rp {{ number_format(
                                $item->unit_price,
                                0,
                                ',',
                                '.'
                            ) }}

                        </td>

                        <td class="text-right">

                            Rp {{ number_format(
                                $item->subtotal,
                                0,
                                ',',
                                '.'
                            ) }}

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>


    {{-- Summary --}}

    <table class="summary">

        <tr>

            <td>
                Subtotal Produk
            </td>

            <td class="text-right">

                Rp {{ number_format(
                    $order->total_product_price,
                    0,
                    ',',
                    '.'
                ) }}

            </td>

        </tr>


        @if($order->voucher_discount_amount > 0)

            <tr>

                <td>
                    Diskon Voucher
                </td>

                <td class="text-right">

                    - Rp {{ number_format(
                        $order->voucher_discount_amount,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endif


        <tr>

            <td>
                Ongkir
            </td>

            <td class="text-right">

                Rp {{ number_format(
                    $order->shipping_fee,
                    0,
                    ',',
                    '.'
                ) }}

            </td>

        </tr>


        <tr class="total">

            <td>
                Total
            </td>

            <td class="text-right">

                Rp {{ number_format(
                    $order->total_payment,
                    0,
                    ',',
                    '.'
                ) }}

            </td>

        </tr>

    </table>


    {{-- Payment --}}

    <div class="section" style="margin-top: 35px;">

        <div class="section-title">
            Pembayaran
        </div>

        <table class="info-table">

            <tr>

                <td style="width: 120px;">
                    Status
                </td>

                <td>
                    {{ strtoupper($order->payment_status) }}
                </td>

            </tr>

            @if($order->payment)

                <tr>

                    <td>
                        Metode
                    </td>

                    <td>
                        {{ $order->payment->payment_type ?? '-' }}
                    </td>

                </tr>

                <tr>

                    <td>
                        Transaction ID
                    </td>

                    <td>
                        {{ $order->payment->transaction_id ?? '-' }}
                    </td>

                </tr>

            @endif

        </table>

    </div>


    {{-- Footer --}}

    <div class="footer">

        Terima kasih telah berbelanja di Melody Furniture.

        <br>

        Invoice ini dibuat secara otomatis oleh sistem.

    </div>

</div>

</body>

</html>