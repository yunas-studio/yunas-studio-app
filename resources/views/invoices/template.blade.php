<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $transaksi->receipt_code }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.35;
            color: #333;
            padding: 5mm;
            margin: 0;
            font-size: 12pt;
        }
        .invoice-container {
            width: 100%;
            max-width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        th, td {
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-container {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .summary-table {
            width: auto;
            min-width: 250px;
            margin-left: auto;
        }
        .summary-table td {
            white-space: nowrap;
        }
        .no-border td {
            border: none;
        }
        .invoice-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<div class="invoice-container">
    <!-- Header -->
    <table class="no-border">
        <tr>
            <td style="text-align: center;">
                @if(file_exists(public_path('assets/images/yunas_dark.png')))
                    <img src="{{ public_path('assets/images/yunas_dark.png') }}" alt="Company Logo" class="invoice-logo">
                @endif
                <p>Jalan Lingkar Selatan, Sukabumi</p>
            </td>
        </tr>
    </table>

    <!-- Customer and Invoice Info -->
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 70%; vertical-align: top;">
                <strong>Invoice Details:</strong><br>
                Invoice #: {{ $transaksi->receipt_code }}<br>
                Date: {{ $transaksi->created_at->format('d M Y, H:i') }}<br>
                Status: {{ $paymentStatusConfig[$transaksi->status] }}<br>
                Process: {{ $processStatusConfig[$transaksi->process_status] }}
            </td>
            <td style="width: 30%; vertical-align: top;">
                <strong>Billed To:</strong><br>
                {{ $transaksi->customer_name }}<br>
                @if($transaksi->phone_number)
                    Phone: {{ $transaksi->phone_number }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table>
        <thead>
        <tr>
            <th style="width: 5%;">No.</th>
            <th style="width: 45%;">Item</th>
            <th style="width: 15%;" class="text-right">Price</th>
            <th style="width: 10%;" class="text-center">Qty</th>
            <th style="width: 15%;" class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        @if($transaksi->packet)
            <tr>
                <td>1</td>
                <td>
                    <strong>{{ $transaksi->packet->name }}</strong><br>
                    {{ $transaksi->packet->product->name ?? '' }}
                </td>
                <td class="text-right">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                <td class="text-center">1</td>
                <td class="text-right">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
            </tr>
        @endif

        @if($transaksi->packet && $transaksi->packet->additionalDefaults->isNotEmpty())
            <tr>
                <td colspan="5"><strong>Included Items:</strong></td>
            </tr>
            @foreach($transaksi->packet->additionalDefaults as $default)
                <tr>
                    <td></td>
                    <td colspan="4">{{ $default->quantity }}x {{ $default->additional->name }}</td>
                </tr>
            @endforeach
        @endif

        @if($transaksi->additionals->isNotEmpty())
            <tr>
                <td colspan="5"><strong>Extra Items:</strong></td>
            </tr>
            @foreach($transaksi->additionals as $additional)
                <tr>
                    <td>{{ $loop->iteration + 1 }}</td>
                    <td>{{ $additional->name }}</td>
                    <td class="text-right">Rp {{ number_format($additional->pivot->price, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $additional->pivot->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>

    <!-- Summary Table -->
    <div class="summary-container">
        <table class="summary-table">
            <tr>
                <td><strong>Subtotal</strong></td>
                <td class="text-right">Rp {{ number_format($transaksi->total_price + $transaksi->discount, 0, ',', '.') }}</td>
            </tr>
            @if($transaksi->discount > 0)
                <tr class="text-danger">
                    <td><strong>Discount</strong></td>
                    <td class="text-right">- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    @if($transaksi->note)
        <table style="margin-top: 15px; width: 100%;">
            <tr>
                <td>
                    <strong>Note:</strong><br>
                    {{ $transaksi->note }}
                </td>
            </tr>
        </table>
    @endif

    <!-- Footer -->
    <table style="margin-top: 20px; width: 100%;" class="no-border">
        <tr>
            <td style="text-align: center;">
                <p>Thank you for your business!</p>
                <p>Invoice generated on {{ now()->format('d M Y H:i') }}</p>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
