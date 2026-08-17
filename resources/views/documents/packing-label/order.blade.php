<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Packing Label - {{ $order->order_number }}
    </title>

    <style>

        @page {
            margin: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
        }

        .label {
            width: 100%;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .brand {
            font-size: 16px;
            font-weight: bold;
        }

        .title {
            margin-top: 3px;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-number {
            margin-top: 8px;
            font-size: 18px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 12px;
        }

        .section-title {
            margin-bottom: 5px;
            font-size: 9px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .recipient {
            font-size: 14px;
            font-weight: bold;
        }

        .address {
            margin-top: 4px;
            line-height: 1.5;
        }

        .shipping-box {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
        }

        .shipping-row {
            margin-bottom: 5px;
        }

        .shipping-row:last-child {
            margin-bottom: 0;
        }

        .label-text {
            color: #6b7280;
        }

        .value {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 6px 5px;
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        td {
            padding: 7px 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .qty {
            width: 45px;
            text-align: center;
            font-weight: bold;
        }

        .sku {
            margin-top: 2px;
            font-size: 9px;
            color: #6b7280;
        }

        .total-box {
            margin-top: 10px;
            border: 2px solid #111827;
            border-radius: 6px;
            padding: 9px;
        }

        .total-row {
            margin-bottom: 4px;
        }

        .total-row:last-child {
            margin-bottom: 0;
        }

        .total-value {
            font-size: 14px;
            font-weight: bold;
        }

        .warehouse-note {
            margin-top: 12px;
            border: 1px dashed #9ca3af;
            padding: 9px;
            min-height: 65px;
        }

        .checklist {
            margin-top: 8px;
        }

        .check {
            display: inline-block;
            margin-right: 15px;
        }

        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }

    </style>

</head>

<body>

<div class="label">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="header">

        <div class="brand">
            Melody Furniture
        </div>

        <div class="title">
            Packing Label
        </div>

        <div class="order-number">
            {{ $order->order_number }}
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- RECIPIENT --}}
    {{-- ===================================================== --}}

    @php
        $address = $order->shipping_address ?? [];
    @endphp

    <div class="section">

        <div class="section-title">
            Penerima
        </div>

        <div class="recipient">
            {{ $address['recipient_name'] ?? $order->customer_name ?? '-' }}
        </div>

        <div>
            {{ $address['phone'] ?? $order->customer_phone ?? '-' }}
        </div>

        <div class="address">

            {{ $address['address'] ?? '-' }}

            <br>

            {{ $address['city'] ?? '-' }},
            {{ $address['province'] ?? '-' }}

            <br>

            {{ $address['postal_code'] ?? '-' }}

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- SHIPPING --}}
    {{-- ===================================================== --}}

    <div class="section">

        <div class="section-title">
            Pengiriman
        </div>

        <div class="shipping-box">

            <div class="shipping-row">

                <span class="label-text">
                    Courier:
                </span>

                <span class="value">
                    {{ strtoupper($order->courier ?? '-') }}
                </span>

            </div>

            <div class="shipping-row">

                <span class="label-text">
                    Service:
                </span>

                <span class="value">
                    {{ $order->shipping_method ?? '-' }}
                </span>

            </div>

            <div class="shipping-row">

                <span class="label-text">
                    Berat:
                </span>

                <span class="value">
                    {{ number_format($order->total_weight ?? 0, 2, ',', '.') }} Kg
                </span>

            </div>

            <div class="shipping-row">

                <span class="label-text">
                    Tracking:
                </span>

                <span class="value">
                    {{ $order->tracking_number ?? '-' }}
                </span>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- ITEMS --}}
    {{-- ===================================================== --}}

    <div class="section">

        <div class="section-title">
            Barang yang Dipacking
        </div>

        <table>

            <thead>

                <tr>

                    <th>
                        Produk
                    </th>

                    <th class="qty">
                        Qty
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

                                <div class="sku">
                                    SKU: {{ $item->product_sku }}
                                </div>

                            @endif

                        </td>

                        <td class="qty">
                            {{ $item->quantity }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>


    {{-- ===================================================== --}}
    {{-- TOTAL ITEM --}}
    {{-- ===================================================== --}}

    <div class="total-box">

        <div class="total-row">

            Total Produk:
            <strong>
                {{ $order->items->count() }} jenis
            </strong>

        </div>

        <div class="total-row">

            Total Quantity:

            <span class="total-value">

                {{ $order->items->sum('quantity') }}

            </span>

            pcs

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- WAREHOUSE --}}
    {{-- ===================================================== --}}

    <div class="warehouse-note">

        <strong>
            CATATAN GUDANG
        </strong>

        <div class="checklist">

            <span class="check">
                □ Barang sesuai
            </span>

            <span class="check">
                □ Packing
            </span>

            <span class="check">
                □ QC
            </span>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- FOOTER --}}
    {{-- ===================================================== --}}

    <div class="footer">

        Dokumen internal gudang —
        {{ $order->order_number }}

    </div>

</div>

</body>

</html>