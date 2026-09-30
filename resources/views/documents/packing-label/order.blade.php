<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Packing Label - {{ $order->order_number }}
    </title>

    <style>

        @page {
            size: 100mm 150mm;
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #000000;
            font-size: 8.5pt;
            line-height: 1.15;
        }

        .label-container {
            width: 100%;
            border: 1.5px solid #000000;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid td,
        table.grid th {
            border: 1px solid #000000;
            padding: 3px 4px;
            vertical-align: top;
        }


        /* =========================================================
            HEADER (LOGOS & SERVICE)
        ========================================================= */

        .header-left {
            width: 50%;
            vertical-align: middle !important;
        }

        .header-right {
            width: 50%;
            text-align: right;
            vertical-align: middle !important;
        }

        .logo {
            max-width: 110px;
            max-height: 28px;
            width: auto;
            height: auto;
        }

        .service-badge {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .order-no {
            font-size: 8pt;
            font-weight: bold;
        }


        /* =========================================================
            COURIER & TRACKING NO
        ========================================================= */

        .tracking-box {
            text-align: center;
            background: #f3f4f6;
            padding: 4px 2px !important;
        }

        .courier-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tracking-number {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }


        /* =========================================================
            ADDRESSES (2 COLUMNS: RECIPIENT & SENDER)
        ========================================================= */

        .col-half {
            width: 50%;
        }

        .addr-title {
            font-size: 7.5pt;
            color: #4b5563;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .person-name {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000000;
        }

        .person-phone {
            font-size: 8.5pt;
            font-weight: bold;
            margin-top: 1px;
        }

        .person-address {
            font-size: 8pt;
            margin-top: 2px;
            line-height: 1.2;
        }


        /* =========================================================
            SHIPPING INFO BAR
        ========================================================= */

        .info-cell {
            font-size: 8pt;
            background: #fafafa;
        }

        .info-label {
            color: #4b5563;
            font-weight: bold;
        }

        .info-val {
            font-weight: bold;
            color: #000000;
        }


        /* =========================================================
            ITEMS TABLE
        ========================================================= */

        .th-product {
            text-align: left;
            background: #e5e7eb;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .th-qty {
            text-align: center;
            width: 35px;
            background: #e5e7eb;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .product-name {
            font-size: 8.5pt;
            font-weight: bold;
        }

        .product-sub {
            font-size: 7.5pt;
            color: #374151;
            margin-top: 1px;
        }

        .td-qty {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            vertical-align: middle !important;
        }


        /* =========================================================
            WARNING & FRAGILE BOX
        ========================================================= */

        .warning-container {
            background: #fef2f2;
            padding: 4px !important;
        }

        .warning-tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .warning-tbl td {
            border: none !important;
            padding: 0 !important;
            vertical-align: middle !important;
        }

        .fragile-td {
            width: 70px;
            text-align: center;
        }

        .fragile-img {
            width: 58px;
            height: auto;
        }

        .warning-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 2px;
        }

        .warning-text {
            font-size: 7.5pt;
            line-height: 1.25;
            color: #991b1b;
            font-weight: bold;
        }


        /* =========================================================
            WAREHOUSE & FOOTER
        ========================================================= */

        .wh-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .wh-checklist {
            font-size: 8pt;
            font-weight: bold;
        }

        .wh-item {
            margin-right: 10px;
        }

        .footer-note {
            text-align: center;
            font-size: 7pt;
            color: #4b5563;
            background: #f9fafb;
            padding: 2px !important;
        }

    </style>

</head>

<body>

@php
    $address = $order->shipping_address ?? [];
@endphp

<div class="label-container">

    <table class="grid">

        {{-- 1. HEADER --}}
        <tr>

            <td class="header-left">

                <img
                    src="{{ $logoBase64 }}"
                    alt="Melody Furniture"
                    class="logo"
                >

            </td>

            <td class="header-right">

                <div class="service-badge">
                    {{ $order->shipping_method ?? 'REGULAR' }}
                </div>

                <div class="order-no">
                    {{ $order->order_number }}
                </div>

            </td>

        </tr>


        {{-- 2. COURIER & TRACKING --}}
        <tr>

            <td
                colspan="2"
                class="tracking-box"
            >

                <div class="courier-name">
                    {{ strtoupper($order->courier ?? 'KURIR') }}
                </div>

                <div class="tracking-number">
                    No. Resi: {{ $order->tracking_number ?? '-' }}
                </div>

            </td>

        </tr>


        {{-- 3. ADDRESSES (PENERIMA & PENGIRIM) --}}
        <tr>

            {{-- Penerima --}}
            <td class="col-half">

                <div class="addr-title">
                    Penerima:
                </div>

                <div class="person-name">
                    {{ $address['recipient_name'] ?? $order->customer_name ?? '-' }}
                </div>

                <div class="person-phone">
                    {{ $address['phone'] ?? $order->customer_phone ?? '-' }}
                </div>

                <div class="person-address">
                    {{ $address['address'] ?? '-' }},
                    {{ $address['area'] ?? '-' }},
                    {{ $address['city'] ?? '-' }},
                    {{ $address['province'] ?? '-' }}
                    {{ $address['postal_code'] ?? '' }}
                </div>

            </td>

            {{-- Pengirim --}}
            <td class="col-half">

                <div class="addr-title">
                    Pengirim:
                </div>

                <div class="person-name">
                    Melody Furniture
                </div>

                <div class="person-phone">
                    0812-3456-7890
                </div>

                <div class="person-address">
                    Kota Malang, Jawa Timur
                </div>

            </td>

        </tr>


        {{-- 4. SHIPPING SUMMARY --}}
        <tr>

            <td
                colspan="2"
                class="info-cell"
            >

                <span class="info-label">Berat:</span>
                <span class="info-val">{{ number_format($order->total_weight ?? 0, 2, ',', '.') }} Kg</span>

                &nbsp;|&nbsp;

                <span class="info-label">Total Jenis:</span>
                <span class="info-val">{{ $order->items->count() }} Item</span>

                &nbsp;|&nbsp;

                <span class="info-label">Total Qty:</span>
                <span class="info-val">{{ $order->items->sum('quantity') }} Pcs</span>

            </td>

        </tr>


        {{-- 5. ITEMS LIST --}}
        <tr>

            <td
                colspan="2"
                style="padding: 0;"
            >

                <table class="grid">

                    <thead>

                        <tr>

                            <th class="th-product">
                                Produk
                            </th>

                            <th class="th-qty">
                                Qty
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($order->items as $item)

                            <tr>

                                <td>

                                    <div class="product-name">
                                        {{ $item->product_name }}
                                    </div>

                                    @if($item->product_variant_name || $item->product_sku)
                                        <div class="product-sub">
                                            @if($item->product_variant_name)
                                                Varian: {{ $item->product_variant_name }}
                                            @endif

                                            @if($item->product_variant_name && $item->product_sku) | @endif

                                            @if($item->product_sku)
                                                SKU: {{ $item->product_sku }}
                                            @endif
                                        </div>
                                    @endif

                                </td>

                                <td class="td-qty">
                                    {{ $item->quantity }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </td>

        </tr>


        {{-- 6. WARNING / FRAGILE BOX --}}
        <tr>

            <td
                colspan="2"
                class="warning-container"
            >

                <table class="warning-tbl">

                    <tr>

                        <td class="fragile-td">

                            <img
                                src="{{ $fragileBase64 }}"
                                alt="Fragile"
                                class="fragile-img"
                            >

                        </td>

                        <td>

                            <div class="warning-title">
                                ⚠ WAJIB VIDEO UNBOXING
                            </div>

                            <div class="warning-text">
                                Harap melakukan video unboxing saat membuka paket untuk syarat klaim garansi jika ada part produk yang rusak/kurang.
                            </div>

                        </td>

                    </tr>

                </table>

            </td>

        </tr>


        {{-- 7. CATATAN GUDANG --}}
        <tr>

            <td colspan="2">

                <div class="wh-title">
                    Catatan Gudang
                </div>

                <div class="wh-checklist">
                    <span class="wh-item">□ Barang Sesuai</span>
                    <span class="wh-item">□ Packing Aman</span>
                    <span class="wh-item">□ QC Passed</span>
                </div>

            </td>

        </tr>


        {{-- 8. FOOTER --}}
        <tr>

            <td
                colspan="2"
                class="footer-note"
            >

                Dokumen Internal Gudang — {{ $order->order_number }}

            </td>

        </tr>

    </table>

</div>

</body>

</html>