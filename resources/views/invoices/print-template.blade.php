<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $transaksi->receipt_code }}</title>
    <style>
        /*
            Print style for Kertas Continuous Form 3 Ply
            Standard size: 9.5in x 11in. Printable area is smaller.
            Width: ~210mm to 215mm is a safe bet for most dot matrix printers.
        */
        @page {
            size: 8.5in 11in; /* Standard letter size, fits well on 9.5x11 continuous paper */
            margin: 12mm 10mm; /* ~0.5 inch margins */
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            margin: 0;
            font-size: 10pt; /* Standard size for dot matrix readability */
        }
        .invoice-container {
            width: 100%;
            margin: 0 auto;
        }
        .header, .footer {
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.5em;
        }
        .header p {
            margin: 2px 0;
            font-size: 0.9em;
        }
        hr {
            border: 0;
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
        }
        th, td {
            padding: 4px 0;
        }
        th {
            text-align: left;
            border-bottom: 1px dashed #000;
        }
        .text-right {
            text-align: right;
        }
        .details-section {
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .summary-section {
            margin-top: 10px;
        }

        /* Print-specific CSS */
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <h1>Yunas Studio</h1>
            <p>Jalan Lingkar Selatan, Sukabumi</p>
            <p>{{ $transaksi->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <hr>

        <div class="details-section">
            <div><strong>Invoice #:</strong> {{ $transaksi->receipt_code }}</div>
            <div><strong>Customer:</strong> {{ $transaksi->customer_name }}</div>
            <div><strong>Cashier:</strong> {{ auth()->user()->name }}</div>
        </div>

        <hr>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                {{-- Packet --}}
                @if($transaksi->packet)
                <tr>
                    <td>{{ $transaksi->packet->name }} ({{ $transaksi->packet->product->name }})</td>
                    <td class="text-right">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                </tr>
                @endif

                {{-- Extra Additionals --}}
                @if($transaksi->additionals->isNotEmpty())
                    <tr><td colspan="2" style="padding-top: 10px;"><strong>Extra Items:</strong></td></tr>
                    @foreach($transaksi->additionals as $item)
                    <tr>
                        <td>{{ $item->pivot->quantity }}x {{ $item->name }}</td>
                        <td class="text-right">Rp {{ number_format($item->pivot->price * $item->pivot->quantity, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endif

                {{-- NEW: Included Items Section --}}
                @if($transaksi->packet && $transaksi->packet->combined_defaults->isNotEmpty())
                    <tr><td colspan="2" style="padding-top: 10px;"><strong>Included Items:</strong></td></tr>
                    @foreach($transaksi->packet->combined_defaults as $default)
                    <tr>
                        <td colspan="2" style="padding-left: 15px;">- {{ $default->quantity }}x {{ $default->name }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <hr>

        <div class="summary-section">
            <table>
                <tbody>
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right">Rp {{ number_format($transaksi->total_price + $transaksi->discount, 0, ',', '.') }}</td>
                    </tr>
                    @if ($transaksi->discount > 0)
                    <tr>
                        <td>Discount</td>
                        <td class="text-right">- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Grand Total</th>
                        <th class="text-right">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</th>
                    </tr>
                    @if($transaksi->status == 'dp' && isset($transaksi->dp_amount))
                    <tr>
                        <td>DP Paid</td>
                        <td class="text-right">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Remaining</th>
                        <th class="text-right">Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}</th>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <hr>

        <div class="footer">
            <p>Thank you for your visit!</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
