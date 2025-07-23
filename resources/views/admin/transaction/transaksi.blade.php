@extends('layouts.master')
@section('title')
    Transaction
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

    @media print {
        body * { visibility: hidden; }
        .invoice-modal .modal-content, .invoice-modal .modal-content * { visibility: visible; }
        .invoice-modal { position: absolute; left: 0; top: 0; }
        .modal-footer { display: none; }
    }
</style>
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Transaksi',
        'pagetitle' => 'Transactions',
        'breadcrumbs' => [['text' => 'Transactions', 'url' => '']]
    ])
    @endcomponent

    <div class="row">
        {{-- Count Cards --}}
        <div class="col-md-6 col-xl-4"><div class="card"><div class="card-body"><div class="float-end mt-2 me-3"><div style="font-size: 2rem"><i class="mdi mdi-file-cancel"></i></div></div><div><h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $belumDibayarCount }}</span></h4><p class="text-muted mb-0">Total Belum Dibayar</p></div></div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card"><div class="card-body"><div class="float-end mt-2 me-3"><div style="font-size: 2rem"><i class="mdi mdi-file-clock"></i></div></div><div><h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $dpCount }}</span></h4><p class="text-muted mb-0">Total DP</p></div></div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card"><div class="card-body"><div class="float-end mt-2 me-3"><div style="font-size: 2rem"><i class="mdi mdi-file-check"></i></div></div><div><h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $sudahDibayarCount }}</span></h4><p class="text-muted mb-0">Total Sudah Dibayar</p></div></div></div></div>
    </div>


    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6"><div class="mb-3"><a href="{{ route('transaksi.create') }}" class="btn btn-success waves-effect waves-light"><i class="mdi mdi-plus me-2"></i> Add New Transaction</a></div></div>
                        <div class="col-md-6"><div class="form-inline float-md-end mb-3"><div class="search-box ms-2"><form action="{{ route('transaksi.index') }}" method="GET"><div class="position-relative"><input type="text" name="search" class="form-control rounded bg-light border-0" placeholder="Search..." value="{{ request('search') }}"><i class="mdi mdi-magnify search-icon"></i></div></form></div></div></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Receipt Code</th><th>Customer</th><th>Packet</th><th>Total Price</th><th>Transaction Date</th><th>Payment Status</th><th>Process Status</th><th>Details</th><th style="width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentStatusConfig = [
                                        'belum dibayar' => ['icon' => '🟡', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'dp' => ['icon' => '🔵', 'class' => 'bg-info-subtle text-info-emphasis'],
                                        'sudah dibayar' => ['icon' => '🟢', 'class' => 'bg-success-subtle text-success-emphasis'],
                                    ];
                                    $processStatusConfig = [
                                        'Belum Foto' => ['icon' => '📷❌','class' => 'bg-light text-dark'],
                                        'Pilih Foto' => ['icon' => '🖼️','class' => 'bg-info-subtle text-info-emphasis'],
                                        'Siap Edit' => ['icon' => '✏️','class' => 'bg-primary-subtle text-primary-emphasis'],
                                        'Proses Edit' => ['icon' => '✏️⚙️','class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'Selesai Editing' => ['icon' => '✏️✅','class' => 'bg-success-subtle text-success-emphasis'],
                                        'Siap Cetak' => ['icon' => '🖨️⚪️','class' => 'bg-primary-subtle text-primary-emphasis'],
                                        'Proses Cetak' => ['icon' => '🖨️⚙️','class' => 'bg-secondary-subtle text-secondary-emphasis'],
                                        'Selesai' => ['icon' => '✅','class' => 'bg-success-subtle text-success-emphasis']
                                    ];
                                @endphp
                                @forelse($transactions as $transaksi)
                                    <tr>
                                        <td><a href="javascript: void(0);" class="text-body fw-bold">{{ $transaksi->receipt_code }}</a></td>
                                        <td>{{ $transaksi->customer_name }}</td>
                                        
                                        {{-- This is the updated column --}}
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
                                                <select name="value"
                                                        class="form-select form-select-sm payment-status-select {{ $paymentStatusConfig[$transaksi->status]['class'] ?? '' }}"
                                                        data-transaction-id="{{ $transaksi->transaction_id }}">
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
                                                         <option value="{{ $status }}" {{ $transaksi->process_status == $status ? 'selected' : '' }}>
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
                                                {{-- Show "Mark Complete" button only at the right stage --}}
                                                @if(in_array($transaksi->process_status, ['Proses Edit', 'Selesai Editing']))
                                                    <form action="{{ route('transaksi.completeEditing', $transaksi) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Mark Editing as Complete">
                                                            <i class="bx bx-check-double"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- Existing Action Icons --}}
                                                @if(in_array($transaksi->process_status, ['Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai']))
                                                    <a href="{{ route('transaksi.view-selections', $transaksi) }}" class="text-warning" data-bs-toggle="tooltip" title="View User's Photo Selections">
                                                        <i class="uil uil-camera-change font-size-18"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('transaksi.edit', $transaksi->transaction_id) }}" class="text-primary" data-bs-toggle="tooltip" title="Edit Transaction"><i class="uil uil-pen font-size-18"></i></a>
                                                <form action="{{ route('transaksi.destroy', $transaksi->transaction_id) }}" method="POST" onsubmit="return confirm('Are you sure?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0" data-bs-toggle="tooltip" title="Delete Transaction"><i class="uil uil-trash-alt font-size-18"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">No transactions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="row mt-4"><div class="col-sm-6"><div><p class="mb-sm-0">Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} entries</p></div></div><div class="col-sm-6"><div class="float-sm-end">{{ $transactions->appends(request()->except('page'))->links() }}</div></div></div>
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
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" onclick="window.print()"><i class="mdi mdi-printer me-1"></i> Print</button></div>
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
                // Set the form action for the modal
                dpForm.action = form.action;
                // Show the modal
                dpModal.show();
            } else {
                // For any other status, submit the form directly
                form.submit();
            }
        });
    });

    // When the modal is shown, focus the input field
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
@endsection