<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 15px;
            border: 1px solid #000;
            border-bottom: 0;
            padding: 4px;
        }

        .company {
            font-weight: 700;
            font-size: 12px;
        }

        .label {
            font-weight: 700;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .items th {
            text-align: center;
            font-weight: 700;
        }

        .items td {
            height: 32px;
        }

        .terms {
            line-height: 1.45;
        }

        .signature {
            height: 92px;
            vertical-align: bottom;
        }
    </style>
</head>
<body>
    <div class="title">Purchase Order</div>

    <table>
        <tr>
            <td style="width: 49%" rowspan="4">
                <div class="company">M/S SHREEYASH CONSTRUCTION</div>
                <div>Crescent pearl - B, B-G/1, Veena Nagar,</div>
                <div>Katrang Road Near St. Anthony Church,</div>
                <div>Khopoli, Maharashtra 410203, Cont - 9923299301</div>
                <br>
                <div>Email : shreeyash.const@gmail.com</div>
                <div>GSTIN : 27AKPPP2912F2Z0</div>
            </td>
            <td style="width: 30%"><span class="label">Purchase Order No:</span><br>{{ $order->po_no }}</td>
            <td style="width: 21%"><span class="label">Dated</span><br>{{ $order->po_date?->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td><span class="label">Supplier's Ref.</span><br>{{ $order->supplier_ref ?: '-' }}</td>
            <td><span class="label">Delivery Date</span><br>{{ $order->delivery_date?->format('d-m-Y') ?: '-' }}</td>
        </tr>
        <tr>
            <td><span class="label">Dispatched through</span><br>{{ $order->dispatched_through ?: '-' }}</td>
            <td><span class="label">Destination</span><br>{{ $order->destination ?: '-' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Delivery Location:</span><br>{{ $order->delivery_location ?: '-' }}</td>
        </tr>
        <tr>
            <td colspan="3">
                <span class="label">Supplier:</span><br>
                <span class="bold">{{ $order->supplier_name }}</span><br>
                {!! nl2br(e($order->supplier_address ?: '-')) !!}<br>
                GSTIN/UIN: {{ $order->supplier_gstin ?: '-' }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 9%">Sr.No</th>
                <th style="width: 35%">Item Description</th>
                <th style="width: 12%">HSN Code</th>
                <th style="width: 12%">Quantity</th>
                <th style="width: 10%">Unit</th>
                <th style="width: 10%">Rate</th>
                <th style="width: 12%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->item_description }}</td>
                    <td class="center">{{ $item->hsn_code }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 3) }}</td>
                    <td class="center">{{ $item->unit }}</td>
                    <td class="right">{{ number_format((float) $item->rate, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach
            @for ($i = $order->items->count(); $i < 8; $i++)
                <tr>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
            <tr>
                <td colspan="6" class="right bold">Subtotal</td>
                <td class="right">{{ number_format((float) $order->subtotal, 2) }}</td>
            </tr>
            @if ((float) $order->cgst_amount > 0)
                <tr><td colspan="6" class="right">CGST</td><td class="right">{{ number_format((float) $order->cgst_amount, 2) }}</td></tr>
            @endif
            @if ((float) $order->sgst_amount > 0)
                <tr><td colspan="6" class="right">SGST</td><td class="right">{{ number_format((float) $order->sgst_amount, 2) }}</td></tr>
            @endif
            @if ((float) $order->igst_amount > 0)
                <tr><td colspan="6" class="right">IGST</td><td class="right">{{ number_format((float) $order->igst_amount, 2) }}</td></tr>
            @endif
            <tr>
                <td colspan="6" class="right bold">Total</td>
                <td class="right bold">{{ number_format((float) $order->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr>
            <td class="terms">
                <span class="label">Terms & Conditions:</span><br>
                {!! nl2br(e($order->terms ?: '-')) !!}
            </td>
        </tr>
        <tr>
            <td class="signature">
                <span class="bold">For Shreeyash Construction</span>
                <br><br><br><br>
                <span class="bold">Authorised Signatory</span>
            </td>
        </tr>
    </table>
</body>
</html>
