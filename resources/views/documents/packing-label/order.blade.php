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


        /* =========================================================
           HEADER
        ========================================================= */

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 62%;
        }

        .logo {
            width: 180px;
            height: auto;
        }

        .title-cell {
            width: 38%;
            text-align: right;
        }

        .title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-number {
            margin-top: 6px;
            font-size: 15px;
            font-weight: bold;
            color: #111827;
        }


        /* =========================================================
           SECTION
        ========================================================= */

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


        /* =========================================================
           RECIPIENT
        ========================================================= */

        .recipient {
            font-size: 14px;
            font-weight: bold;
        }

        .address {
            margin-top: 4px;
            line-height: 1.5;
        }


        /* =========================================================
           SHIPPING
        ========================================================= */

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


        /* =========================================================
           ITEMS
        ========================================================= */

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


        /* =========================================================
           TOTAL ITEM
        ========================================================= */

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


        /* =========================================================
           WARNING / FRAGILE
        ========================================================= */

        .warning-box {
            margin-top: 12px;
            border: 2px solid #dc2626;
            background: #fef2f2;
            border-radius: 6px;
            padding: 9px;
        }

        .warning-table {
            width: 100%;
            border-collapse: collapse;
        }

        .warning-table td {
            padding: 0;
            border: none;
            vertical-align: middle;
        }

        .fragile-cell {
            width: 105px;
            text-align: center;
            padding-right: 8px !important;
        }

        .fragile-image {
            width: 85px;
            height: auto;
        }

        .warning-content {
            vertical-align: top !important;
        }

        .warning-title {
            font-size: 11px;
            font-weight: bold;
            color: #b91c1c;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .warning-text {
            font-size: 9px;
            line-height: 1.45;
            color: #7f1d1d;
            text-align: justify;
        }


        /* =========================================================
           WAREHOUSE
        ========================================================= */

        .warehouse-note {
            margin-top: 12px;
            border: 1px dashed #9ca3af;
            padding: 9px;
            min-height: 65px;
        }

        .warehouse-title {
            font-size: 10px;
            font-weight: bold;
        }

        .checklist {
            margin-top: 8px;
        }

        .check {
            display: inline-block;
            margin-right: 15px;
        }


        /* =========================================================
           FOOTER
        ========================================================= */

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

        <table class="header-table">

            <tr>

                {{-- Logo --}}
                <td class="logo-cell">

                    <img
                        src="{{ $logoBase64 }}"
                        alt="Melody Furniture"
                        class="logo"
                    >

                </td>


                {{-- Document Information --}}
                <td class="title-cell">

                    <div class="title">
                        Packing Label
                    </div>

                    <div class="order-number">
                        {{ $order->order_number }}
                    </div>

                </td>

            </tr>

        </table>

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
    {{-- WARNING + FRAGILE --}}
    {{-- ===================================================== --}}

    <div class="warning-box">

        <table class="warning-table">

            <tr>

                {{-- Fragile Image --}}
                <td class="fragile-cell">

                    <img
                        src="{{ $fragileBase64 }}"
                        alt="Fragile"
                        class="fragile-image"
                    >

                </td>


                {{-- Warning Text --}}
                <td class="warning-content">

                    <div class="warning-title">
                        ⚠ WARNING
                    </div>

                    <div class="warning-text">

                        Harap melakukan video unboxing untuk syarat klaim
                        garansi jika ada kerusakan atau kekurangan pada part
                        produk. Tanpa video unboxing, klaim garansi tidak
                        berlaku.

                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ===================================================== --}}
    {{-- WAREHOUSE --}}
    {{-- ===================================================== --}}

    <div class="warehouse-note">

        <div class="warehouse-title">
            CATATAN GUDANG
        </div>

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