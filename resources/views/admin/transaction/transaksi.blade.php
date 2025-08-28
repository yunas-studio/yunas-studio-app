@extends('layouts.master')
@section('title')
    Transaction
@endsection

@php
    use Illuminate\Support\Str;
@endphp

@section('css')
<style>
    .invoice-modal .modal-dialog { max-width: 800px; }
    .invoice-modal .invoice-header { background-color: #f8f9fa; padding: 2rem; border-bottom: 1px solid #dee2e6; }
    .invoice-modal .invoice-logo { max-height: 60px; }
    .invoice-modal .invoice-details-table th,
    .invoice-modal .invoice-details-table td { border: none; }
    .clickable-price { cursor: pointer; color: inherit; text-decoration: none; }
    .clickable-price:hover { text-decoration: underline; color: #556ee6; }
    .sortable-header { cursor: pointer; position: relative; padding-right: 20px; }
    .sortable-header .sort-icon { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); opacity: 0.4; }
    .sortable-header.active .sort-icon { opacity: 1; color: #556ee6; }
    .advanced-filter-toggler { text-decoration: none; font-size: 0.9em; }
    a.disabled { pointer-events: none; opacity: 0.65; }
</style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Transaksi', 'pagetitle' => 'Transactions', 'breadcrumbs' => [['text' => 'Transactions', 'url' => '']]])
    @endcomponent

    {{-- Summary Cards Row --}}
    <div class="row">
        <div class="col-lg-9">
            <div class="row">
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Total Belum Dibayar</p>
                                    <h4 class="mb-0 text-warning">Rp {{ number_format($totalBelumDibayar, 0, ',', '.') }}</h4>
                                    <p class="text-muted mb-0 font-size-12">from {{ $countBelumDibayar }} {{ Str::plural('transaction', $countBelumDibayar) }}</p>
                                </div>
                                <div class="flex-shrink-0 align-self-center">
                                    <i class="bx bx-error-circle font-size-24 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Total DP (Paid)</p>
                                    <h4 class="mb-0 text-info">Rp {{ number_format($totalDpPaid, 0, ',', '.') }}</h4>
                                    <p class="text-muted mb-0 font-size-12">from {{ $countDp }} {{ Str::plural('transaction', $countDp) }}</p>
                                </div>
                                <div class="flex-shrink-0 align-self-center">
                                    <i class="bx bx-time-five font-size-24 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Total Sudah Dibayar</p>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($totalSudahDibayar, 0, ',', '.') }}</h4>
                                    <p class="text-muted mb-0 font-size-12">from {{ $countSudahDibayar }} {{ Str::plural('transaction', $countSudahDibayar) }}</p>
                                </div>
                                <div class="flex-shrink-0 align-self-center">
                                    <i class="bx bx-check-circle font-size-24 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="fw-medium">Total Profit (Filtered)</p>
                            <h4 class="mb-0 text-white">Rp {{ number_format($totalProfit, 0, ',', '.') }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <i class="bx bx-wallet font-size-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Filter Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('transaksi.index') }}" method="GET">
                        <div class="row">
                            <div class="col-12">
                                <label for="search" class="form-label">Search Transaction</label>
                                <input type="text" name="search" class="form-control" placeholder="Search by name or receipt code..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="mt-2">
                             <a class="advanced-filter-toggler" data-bs-toggle="collapse" href="#advancedFilters" role="button" aria-expanded="false" aria-controls="advancedFilters">
                                <i class="bx bx-slider-alt me-1"></i> Advanced Filters
                            </a>
                        </div>

                        @php
                            $isAdvancedFilterActive = request()->filled('payment_status') || request()->filled('process_status') || request()->filled('packet_id') || request()->filled('start_date') || request()->filled('end_date');
                        @endphp

                        <div class="collapse {{ $isAdvancedFilterActive ? 'show' : '' }}" id="advancedFilters">
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <select class="form-select" name="payment_status" id="payment_status">
                                        <option value="">All</option>
                                        @foreach($paymentStatuses as $status)
                                            <option value="{{ $status }}" {{ request('payment_status') == $status ? 'selected' : '' }}>{{ ucwords($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="process_status" class="form-label">Process Status</label>
                                    <select class="form-select" name="process_status" id="process_status">
                                        <option value="">All</option>
                                        @foreach($processStatuses as $status)
                                            <option value="{{ $status }}" {{ request('process_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="packet_id" class="form-label">Packet</label>
                                    <select class="form-select" name="packet_id" id="packet_id">
                                        <option value="">All Packets</option>
                                        @foreach($packetsForFilter as $productName => $packets)
                                            <optgroup label="{{ $productName }}">
                                                @foreach($packets as $packet)
                                                    <option value="{{ $packet->id }}" {{ request('packet_id') == $packet->id ? 'selected' : '' }}>
                                                        {{ $packet->name }} - {{ $productName }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <label class="form-label mb-0">Date Range</label>
                                </div>
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label small text-muted">From</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label small text-muted">To</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                             <a href="{{ route('transaksi.index') }}" class="btn btn-secondary"><i class="bx bx-reset me-1"></i> Reset</a>
                            <button type="submit" class="btn btn-primary"><i class="bx bx-filter-alt me-1"></i> Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a href="{{ route('transaksi.create') }}" class="btn btn-success waves-effect waves-light"><i class="mdi mdi-plus me-2"></i> Add New Transaction</a>
                                <button type="button" id="hide-completed-btn" class="btn btn-secondary waves-effect waves-light">
                                    <i class="bx bx-hide me-1"></i> Hide Completed
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    @php
                                        function sortable_header($label, $column, $request) {
                                            $sortBy = $request->input('sort_by');
                                            $sortDirection = $request->input('sort_direction', 'asc');
                                            $isActive = ($sortBy === $column);
                                            $newDirection = ($isActive && $sortDirection === 'asc') ? 'desc' : 'asc';
                                            $icon = $isActive ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort';
                                            $url = $request->fullUrlWithQuery(['sort_by' => $column, 'sort_direction' => $newDirection]);
                                            return '<a href="' . $url . '" class="text-dark sortable-header' . ($isActive ? ' active' : '') . '">' . $label . '<i class="bx ' . $icon . ' sort-icon"></i></a>';
                                        }
                                    @endphp
                                    <th>Receipt Code</th>
                                    <th>Customer</th>
                                    <th>Packet</th>
                                    <th>{!! sortable_header('Total Price', 'total_price', request()) !!}</th>
                                    <th>{!! sortable_header('Transaction Date', 'created_at', request()) !!}</th>
                                    <th>Payment Status</th>
                                    <th>Process Status</th>
                                    <th>Details</th>
                                    <th style="width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentStatusConfig = ['belum dibayar' => ['icon' => '🟡', 'class' => 'bg-warning-subtle text-warning-emphasis'],'dp' => ['icon' => '🔵', 'class' => 'bg-info-subtle text-info-emphasis'],'sudah dibayar' => ['icon' => '🟢', 'class' => 'bg-success-subtle text-success-emphasis'],];
                                    $processStatusConfig = ['Belum Foto' => ['icon' => '📷❌','class' => 'bg-light text-dark'],'Pilih Foto' => ['icon' => '🖼️','class' => 'bg-info-subtle text-info-emphasis'],'Siap Edit' => ['icon' => '✏️','class' => 'bg-primary-subtle text-primary-emphasis'],'Proses Edit' => ['icon' => '✏️⚙️','class' => 'bg-warning-subtle text-warning-emphasis'],'Selesai Editing' => ['icon' => '✏️✅','class' => 'bg-success-subtle text-success-emphasis'],'Siap Cetak' => ['icon' => '🖨️⚪️','class' => 'bg-primary-subtle text-primary-emphasis'],'Proses Cetak' => ['icon' => '🖨️⚙️','class' => 'bg-secondary-subtle text-secondary-emphasis'],'Selesai' => ['icon' => '✅','class' => 'bg-success-subtle text-success-emphasis']];
                                @endphp
                                @forelse($transactions as $transaksi)
                                    <tr data-process-status="{{ $transaksi->process_status }}" data-payment-status="{{ $transaksi->status }}">
                                        <td><a href="javascript: void(0);" class="text-body fw-bold">{{ $transaksi->receipt_code }}</a></td>
                                        <td>{{ $transaksi->customer_name }}</td>
                                        <td>
                                            <span class="fw-bold">{{ $transaksi->packet->name ?? 'N/A' }}</span>
                                            @if($transaksi->packet && $transaksi->packet->product)
                                                <br>
                                                <small class="text-muted">{{ $transaksi->packet->product->name }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-bold"><a href="javascript:void(0);" class="clickable-price" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</a></td>
                                        <td>{{ $transaksi->created_at->format('d M Y, H:i') }}</td>
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST" class="status-update-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="field" value="status">
                                                <select name="value" class="form-select form-select-sm payment-status-select {{ $paymentStatusConfig[$transaksi->status]['class'] ?? '' }}" data-transaction-id="{{ $transaksi->transaction_id }}">
                                                    @foreach ($paymentStatusConfig as $status => $config)
                                                        <option value="{{ $status }}" {{ $transaksi->status == $status ? 'selected' : '' }}>
                                                            {{ $config['icon'] }} {{ ucwords($status) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="field" value="process_status">
                                                <select name="value" class="form-select form-select-sm {{ $processStatusConfig[$transaksi->process_status]['class'] ?? '' }}" onchange="this.form.submit()">
                                                     @foreach ($processStatusConfig as $status => $config)
                                                        @php
                                                            $isPrintStatus = in_array($status, ['Siap Cetak', 'Proses Cetak']);
                                                            $canPrint = $transaksi->hasPrintableItems();
                                                        @endphp
                                                         <option value="{{ $status }}" 
                                                            {{ $transaksi->process_status == $status ? 'selected' : '' }}
                                                            {{ $isPrintStatus && !$canPrint ? 'disabled' : '' }}>
                                                             {{ $config['icon'] }} {{ $status }}
                                                         </option>
                                                     @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm btn-rounded" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">View</button>
                                        </td>
                                        <td>
                                           <div class="d-flex align-items-center gap-2">
                                                @php $canViewSelections = in_array($transaksi->process_status, ['Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai']); @endphp
                                                <a href="{{ $canViewSelections ? route('transaksi.view-selections', $transaksi) : '#' }}" 
                                                   class="text-warning {{ !$canViewSelections ? 'disabled' : '' }}" 
                                                   data-bs-toggle="tooltip" 
                                                   title="{{ $canViewSelections ? 'View User\'s Photo Selections' : 'Action not available until photos are selected' }}">
                                                    <i class="uil uil-camera-change font-size-18"></i>
                                                </a>
                                                
                                                <a href="{{ route('transaksi.edit', $transaksi->transaction_id) }}" class="text-primary" data-bs-toggle="tooltip" title="Edit Transaction"><i class="uil uil-pen font-size-18"></i></a>
                                                
                                                @if(!empty($transaksi->phone_number) && $transaksi->user)
                                                    @php
                                                        $waMessages = [
                                                            'Belum Foto' => "Halo kak {$transaksi->customer_name}, kami ingin mengingatkan bahwa jadwal foto anda belum terlaksana. Silakan hubungi kami untuk penjadwalan ulang. Terima kasih.",
                                                            'Pilih Foto' => "Halo kak {$transaksi->customer_name}, terima kasih telah melakukan sesi foto. Silakan pilih foto yang akan diedit melalui link di bawah ini. Login menggunakan username dan password berikut:\n\n" .
                                                                            "Username: {$transaksi->user->username}\n" .
                                                                            "Password: {$transaksi->user->username}\n\n" .
                                                                            "Link Pemilihan Foto:\n" . route('transaksi.view-select-for-edit', $transaksi),
                                                            'Selesai Editing' => "Halo kak {$transaksi->customer_name}, foto anda telah selesai diedit. Silakan datang untuk proses pencetakan atau konfirmasi kepada kami.",
                                                            'Selesai' => "Terima kasih atas kunjungannya, kami sampaikan bahwa foto anda telah selesai dicetak. Silakan anda ambil hasil cetak anda di Yuna's Studio, Kota Sukabumi.\n\nBerikan rating terbaik anda melalui link berikut:\nhttps://share.google/hbH82FzldhrdNS53M"
                                                        ];
                                                        $waLink = null;
                                                        if (isset($waMessages[$transaksi->process_status])) {
                                                            $waMessage = $waMessages[$transaksi->process_status];
                                                            $phoneNumber = preg_replace('/\D/', '', $transaksi->phone_number);
                                                            if (strpos($phoneNumber, '0') === 0) {
                                                                $phoneNumber = '62' . substr($phoneNumber, 1);
                                                            }
                                                            $waLink = "https://api.whatsapp.com/send?phone={$phoneNumber}&text=" . urlencode($waMessage);
                                                        }
                                                    @endphp
                                                    @if(isset($waLink))
                                                        <a href="{{ $waLink }}" target="_blank" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Kirim WhatsApp"><i class="uil uil-whatsapp"></i></a>
                                                    @endif
                                                @endif

                                                <form action="{{ route('transaksi.destroy', $transaksi->transaction_id) }}" method="POST" onsubmit="return confirm('Are you sure?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0" data-bs-toggle="tooltip" title="Delete Transaction"><i class="uil uil-trash-alt font-size-18"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">No transactions found for the selected filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                     <div class="row mt-4">
                        <div class="col-sm-6 d-flex align-items-center">
                            <div>
                                <p class="mb-sm-0">Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} entries</p>
                            </div>
                            <div class="ms-3">
                                <form method="GET" action="{{ route('transaksi.index') }}" class="d-flex align-items-center">
                                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ is_array($value) ? http_build_query($value) : $value }}">
                                    @endforeach
                                    <label for="per_page" class="form-label me-2 mb-0">Show:</label>
                                    <select name="per_page" id="per_page" class="form-select form-select-sm" style="width: 70px;" onchange="this.form.submit()">
                                        @foreach($perPageOptions as $option)
                                            <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-end">
                                {{ $transactions->withQueryString()->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($transactions as $transaksi)
        <div id="detailModal{{ $transaksi->transaction_id }}" class="modal fade invoice-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="invoice-content" id="invoiceContent{{ $transaksi->transaction_id }}">
                            <div class="invoice-header text-center"><div class="mb-3"><img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="logo" class="invoice-logo"/></div><p class="text-muted mb-0">Jalan Lingkar Selatan, Sukabumi</p></div>
                            <div class="p-4">
                                <div class="row"><div class="col-md-6"><h5 class="font-size-16">Billed To:</h5><p class="mb-1">{{ $transaksi->customer_name }}</p>@if($transaksi->phone_number)<p class="mb-1 text-muted">{{ $transaksi->phone_number }}</p>@endif @if($transaksi->user)<p class="mb-1 text-muted"><i class="mdi mdi-account-circle-outline me-1"></i> Linked to User: {{ $transaksi->user->name }}</p>@endif</div><div class="col-md-6 text-md-end"><h5 class="font-size-16">Invoice Details:</h5><p class="mb-1"><strong>Invoice #:</strong> {{ $transaksi->receipt_code }}</p><p class="mb-1"><strong>Transaction Date:</strong> {{ $transaksi->created_at->format('d M Y, H:i') }}</p><p class="mb-1"><strong>Payment Status:</strong> {{ ucwords($transaksi->status) }}</p><p class="mb-1"><strong>Process Status:</strong> {{ $transaksi->process_status }}</p></div></div>
                                <div class="py-2 mt-3"><h3 class="font-size-15 fw-bold">Order Summary</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap">
                                        <thead class="table-light"><tr><th style="width: 70px;">No.</th><th>Item</th><th class="text-end">Price</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @if($transaksi->packet)<tr><td>1</td><td><h5 class="font-size-15 mb-0">{{ $transaksi->packet->name }}</h5><span class="text-muted">{{ $transaksi->packet->product->name ?? '' }}</span></td><td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td><td class="text-center">1</td><td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td></tr>@endif
                                            @if($transaksi->packet && $transaksi->packet->additionalDefaults->isNotEmpty())<tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted">Included Items:</strong></td></tr>@foreach($transaksi->packet->additionalDefaults as $default)<tr><td><i class="mdi mdi-circle-small text-muted"></i></td><td colspan="4">{{ $default->quantity }}x {{ $default->additional->name }}</td></tr>@endforeach @endif
                                            @if($transaksi->additionals->isNotEmpty())<tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted">Extra Items:</strong></td></tr>@foreach($transaksi->additionals as $additional)<tr><td><i class="mdi mdi-circle-small text-muted"></td><td><h5 class="font-size-15 mb-0">{{ $additional->name }}</h5><span class="text-muted">Additional Item</span></td><td class="text-end">Rp {{ number_format($additional->pivot->price, 0, ',', '.') }}</td><td class="text-center">{{ $additional->pivot->quantity }}</td><td class="text-end">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td></tr>@endforeach @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <div class="w-50">
                                        <table class="table table-nowrap invoice-details-table">
                                            <tbody>
                                                <tr><td class="fw-bold">Subtotal</td><td class="text-end">Rp {{ number_format($transaksi->total_price + $transaksi->discount, 0, ',', '.') }}</td></tr>
                                                @if ($transaksi->discount > 0)<tr class="text-danger"><td class="fw-bold">Discount</td><td class="text-end">- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</td></tr>@endif
                                                <tr class="border-top"><td class="fw-bold">Grand Total</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @if($transaksi->status == 'dp' && isset($transaksi->dp_amount))
                                                    <tr><td class="fw-bold">DP Paid</td><td class="text-end">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                    <tr class="fs-5 bg-light"><td class="fw-bold">Remaining Balance</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                @elseif($transaksi->status == 'sudah dibayar')
                                                    <tr class="fs-5 bg-light"><td class="fw-bold">Total Paid</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @else
                                                     <tr class="fs-5 bg-light"><td class="fw-bold">Total Due</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @if($transaksi->note)<hr><div class="py-2"><h5 class="font-size-15">Note:</h5><p class="text-muted fst-italic">{{ $transaksi->note }}</p></div>@endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><a href="{{ route('transaksi.print-invoice', $transaksi) }}" target="_blank" class="btn btn-primary"><i class="mdi mdi-printer me-1"></i> Print Receipt</a></div>
                </div>
            </div>
        </div>
    @endforeach

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dpModal = new bootstrap.Modal(document.getElementById('dpAmountModal'));
    const dpForm = document.getElementById('dpAmountForm');
    const dpInput = document.getElementById('dp_amount_modal');

    document.querySelectorAll('.payment-status-select').forEach(selectElement => {
        selectElement.addEventListener('change', function (e) {
            const selectedStatus = e.target.value;
            const form = e.target.closest('form');

            if (selectedStatus === 'dp') {
                dpForm.action = form.action;
                dpModal.show();
            } else {
                form.submit();
            }
        });
    });

    document.getElementById('dpAmountModal').addEventListener('shown.bs.modal', function () {
        dpInput.focus();
    });
});
</script>
@endsection

@section('script-bottom')
<div class="modal fade" id="dpAmountModal" tabindex="-1" aria-labelledby="dpAmountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dpAmountForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="field" value="status">
                <input type="hidden" name="value" value="dp">
                <div class="modal-header">
                    <h5 class="modal-title" id="dpAmountModalLabel">Enter Down Payment Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dp_amount_modal" class="form-label">DP Amount (Rp)</label>
                        <input type="number" class="form-control" id="dp_amount_modal" name="dp_amount" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save DP Amount</button>
                </div>
            </form>
        </div>
    </div>
</div>
    
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('hide-completed-btn');
        const processStatusToHide = 'Selesai';
        const paymentStatusToHide = 'sudah dibayar';

        function updateView(isHidden) {
            const rows = document.querySelectorAll(`tr[data-process-status]`);
            
            rows.forEach(row => {
                const isCompleted = row.dataset.processStatus === processStatusToHide && row.dataset.paymentStatus === paymentStatusToHide;
                if (isCompleted && isHidden) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });

            if (isHidden) {
                toggleBtn.innerHTML = `<i class="bx bx-show me-1"></i> Show Completed`;
                toggleBtn.classList.remove('btn-secondary');
                toggleBtn.classList.add('btn-info');
            } else {
                toggleBtn.innerHTML = `<i class="bx bx-hide me-1"></i> Hide Completed`;
                toggleBtn.classList.remove('btn-info');
                toggleBtn.classList.add('btn-secondary');
            }
        }

        let isCompletedHidden = localStorage.getItem('hideCompleted') === 'true';
        updateView(isCompletedHidden);

        toggleBtn.addEventListener('click', function() {
            isCompletedHidden = !isCompletedHidden;
            localStorage.setItem('hideCompleted', isCompletedHidden);
            updateView(isCompletedHidden);
        });
    });
</script>
@endsection
