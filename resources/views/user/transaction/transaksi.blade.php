@extends('layouts.master')
@section('title')
    My Transactions
@endsection

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
    .error-container { position: fixed; top: 20px; right: 20px; z-index: 1100; max-width: 400px; }
    .error-message { box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: all 0.3s ease; }
    /* Removed amount hiding styles */
</style>
@endsection

@php
    $paymentStatusConfig = ['belum dibayar' => ['icon' => '🟡', 'class' => 'bg-warning-subtle text-warning-emphasis'],'dp' => ['icon' => '🔵', 'class' => 'bg-info-subtle text-info-emphasis'],'sudah dibayar' => ['icon' => '🟢', 'class' => 'bg-success-subtle text-success-emphasis'],];
    $processStatusConfig = ['Belum Foto' => ['icon' => '📷❌','class' => 'bg-light text-dark'],'Pilih Foto' => ['icon' => '🖼️','class' => 'bg-info-subtle text-info-emphasis'],'Siap Edit' => ['icon' => '✏️','class' => 'bg-primary-subtle text-primary-emphasis'],'Proses Edit' => ['icon' => '✏️⚙️','class' => 'bg-warning-subtle text-warning-emphasis'],'Selesai Editing' => ['icon' => '✏️✅','class' => 'bg-success-subtle text-success-emphasis'],'Siap Cetak' => ['icon' => '🖨️⚪️','class' => 'bg-primary-subtle text-primary-emphasis'],'Proses Cetak' => ['icon' => '🖨️⚙️','class' => 'bg-secondary-subtle text-secondary-emphasis'],'Selesai' => ['icon' => '✅','class' => 'bg-success-subtle text-success-emphasis']];
@endphp

@section('content')
    <div class="error-container">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show error-message" role="alert">
                <i class="mdi mdi-alert-circle-outline me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    @component('common-components.breadcrumb', ['title' => 'My Transactions', 'pagetitle' => 'Transactions', 'breadcrumbs' => [['text' => 'My Transactions', 'url' => '']]])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
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
                            @forelse($transactions as $transaksi)
                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">{{ $transaksi->receipt_code }}</a></td>
                                    <td>
                                        <span class="fw-bold">{{ $transaksi->packet->name ?? 'N/A' }}</span>
                                        @if($transaksi->packet && $transaksi->packet->product)
                                            <br>
                                            <small class="text-muted">{{ $transaksi->packet->product->name }}</small>
                                        @endif
                                    </td>
                                    <td class="fw-bold">
                                        
                                            Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}
                                        </a>
                                    </td>
                                    <td>{{ $transaksi->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="badge {{ $paymentStatusConfig[$transaksi->status]['class'] ?? '' }}">{{ $paymentStatusConfig[$transaksi->status]['icon'] ?? '' }} {{ ucwords($transaksi->status) }}</span></td>
                                    <td><span class="badge {{ $processStatusConfig[$transaksi->process_status]['class'] ?? '' }}">{{ $processStatusConfig[$transaksi->process_status]['icon'] ?? '' }} {{ $transaksi->process_status }}</span></td>
                                    <td><button type="button" class="btn btn-primary btn-sm btn-rounded" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">View</button></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            @if (in_array($transaksi->process_status, ['Pilih Foto', 'Siap Edit']))
                                                <a href="{{ route('transaksi.view-select-for-edit', $transaksi) }}" class="text-warning" data-bs-toggle="tooltip" title="Select photos for editing and printing">
                                                    <i class="uil uil-edit-alt font-size-18"></i>
                                                </a>
                                            @endif
                                            
                                            @if (in_array($transaksi->process_status, ['Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai']))
                                                @if ($transaksi->status === 'sudah dibayar')
                                                    <a href="{{ route('transaksi.view-result-photos', $transaksi) }}" class="text-success" data-bs-toggle="tooltip" title="View and download your final photos">
                                                        <i class="uil uil-camera font-size-18"></i>
                                                    </a>
                                                @else
                                                    @php
                                                        $remaining = $transaksi->total_price - ($transaksi->dp_amount ?? 0);
                                                    @endphp
                                                    <a href="javascript:void(0);" 
                                                       class="text-muted" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Please complete your payment to view final photos" 
                                                       onclick="showPaymentAlertModal('Rp {{ number_format($remaining, 0, ',', '.') }}')">
                                                        <i class="uil uil-camera font-size-18"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4"><p class="mb-0">You have no transactions that match the current filters.</p></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                     <div class="row mt-4">
                        @if($transactions->total() > 0)
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
                        @endif
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
                                <div class="row"><div class="col-md-6"><h5 class="font-size-16">Billed To:</h5><p class="mb-1">{{ $transaksi->customer_name }}</p>@if($transaksi->phone_number)<p class="mb-1 text-muted">{{ $transaksi->phone_number }}</p>@endif</div><div class="col-md-6 text-md-end"><h5 class="font-size-16">Invoice Details:</h5><p class="mb-1"><strong>Invoice #:</strong> {{ $transaksi->receipt_code }}</p><p class="mb-1"><strong>Transaction Date:</strong> {{ $transaksi->created_at->format('d M Y, H:i') }}</p><p class="mb-1"><strong>Payment Status:</strong> {{ ucwords($transaksi->status) }}</p><p class="mb-1"><strong>Process Status:</strong> {{ $transaksi->process_status }}</p></div></div>
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        @if($transaksi->status == "sudah dibayar")
                            <a href="{{ route('transaksi.download-invoice', $transaksi) }}"
                               class="btn btn-primary"
                               target="_blank">
                                <i class="mdi mdi-file-pdf-box me-1"></i> Download PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Payment Alert Modal -->
    <div class="modal fade" id="paymentAlertModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentAlertModalLabel">Payment Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bx bx-lock-alt bx-lg text-warning mb-2"></i>
                        <p id="alertModalBody"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        let paymentAlertModal;
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('paymentAlertModal')) {
                paymentAlertModal = new bootstrap.Modal(document.getElementById('paymentAlertModal'));
            }
            
            // Handle amount visibility toggle
            document.querySelectorAll('.toggle-amount-visibility').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent event bubbling
                    const amountContainer = this.closest('.amount-container');
                    const amountValue = amountContainer.querySelector('.amount-value');
                    
                    if (amountValue.classList.contains('amount-hidden')) {
                        // Show amount
                        amountValue.classList.remove('amount-hidden');
                        this.classList.remove('bx-hide');
                        this.classList.add('bx-show-alt');
                    } else {
                        // Hide amount
                        amountValue.classList.add('amount-hidden');
                        this.classList.remove('bx-show-alt');
                        this.classList.add('bx-hide');
                    }
                });
            });

            // Initialize all amounts as hidden
            document.querySelectorAll('.amount-value').forEach(el => {
                el.classList.add('amount-hidden');
            });
            document.querySelectorAll('.toggle-amount-visibility').forEach(el => {
                el.classList.remove('bx-show-alt');
                el.classList.add('bx-hide');
            });
        });

        function showPaymentAlertModal(remainingAmount) {
            const modalBody = document.getElementById('alertModalBody');
            if (modalBody) {
                modalBody.textContent = `You must complete your remaining payment of ${remainingAmount} to access your final photos. Please contact us to complete your payment.`;
            }
            if (paymentAlertModal) {
                paymentAlertModal.show();
            }
        }
    </script>
@endsection