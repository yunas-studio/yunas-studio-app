@extends('layouts.master')
@section('title')
    Transaction
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Transaksi',
        'pagetitle' => 'Transactions',
        'breadcrumbs' => [['text' => 'Transactions', 'url' => '']]
    ])
    @endcomponent

    {{-- Count Cards ... (remain the same) ... --}}
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2 me-3"><div style="font-size: 2rem"><i class="mdi mdi-file-cancel"></i></div></div>
                    <div><h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $belumDibayarCount }}</span></h4><p class="text-muted mb-0">Total Belum Dibayar</p></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2 me-3"><div style="font-size: 2rem"><i class="mdi mdi-file-clock"></i></div></div>
                    <div><h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $dpCount }}</span></h4><p class="text-muted mb-0">Total DP</p></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2 me-3"><div style="font-size: 2rem"><i class="mdi mdi-file-check"></i></div></div>
                    <div><h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $sudahDibayarCount }}</span></h4><p class="text-muted mb-0">Total Sudah Dibayar</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6"><div class="mb-3"><a href="{{ route('transaksi.create') }}" class="btn btn-success waves-effect waves-light"><i class="mdi mdi-plus me-2"></i> Add New Transaction</a></div></div>
                        <div class="col-md-6"><div class="form-inline float-md-end mb-3"><div class="search-box ms-2">
                            <form action="{{ route('transaksi.index') }}" method="GET"><div class="position-relative"><input type="text" name="search" class="form-control rounded bg-light border-0" placeholder="Search..." value="{{ request('search') }}"><i class="mdi mdi-magnify search-icon"></i></div></form>
                        </div></div></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20px;"><div class="form-check font-size-16"><input type="checkbox" class="form-check-input" id="customCheck1"><label class="form-check-label" for="customCheck1">&nbsp;</label></div></th>
                                    <th>Receipt Code</th>
                                    <th>Customer Name</th>
                                    <th>Products</th>
                                    <th>Booking DateTime</th> {{-- Changed from Transaction Date --}}
                                    <th>Status Pembayaran</th>
                                    <th>Status</th>
                                    <th>Detail</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaksi)
                                    <tr>
                                        <td><div class="form-check font-size-16"><input type="checkbox" class="form-check-input" id="customCheck{{$transaksi->transaction_id}}"><label class="form-check-label" for="customCheck{{$transaksi->transaction_id}}">&nbsp;</label></div></td>
                                        <td><a href="javascript: void(0);" class="text-body fw-bold">{{ $transaksi->receipt_code }}</a></td>
                                        <td>{{ $transaksi->customer_name }}</td>
                                        <td>{{ $transaksi->products->isNotEmpty() ? $transaksi->products->pluck('name')->implode(', ') : 'N/A' }}</td>
                                        <td>
                                            @if($transaksi->booking)
                                                {{ \Carbon\Carbon::parse($transaksi->booking->booking_datetime)->format('d M Y, H:i') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaksi->status == 'belum dibayar') <span class="badge bg-warning-subtle text-warning">Belum Dibayar</span>
                                            @elseif($transaksi->status == 'dp') <span class="badge bg-primary-subtle text-primary">DP</span>
                                            @else <span class="badge bg-success-subtle text-success">Sudah Dibayar</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('transaksi.toggle-status', $transaksi->transaction_id) }}" method="POST" class="d-inline">
                                                @csrf @method('PUT')
                                                <div class="form-check form-switch"><input type="checkbox" class="form-check-input" id="statusToggle{{ $transaksi->transaction_id }}" onchange="this.form.submit()" {{ $transaksi->isActive ? 'checked' : '' }}></div>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#myModal{{ $transaksi->transaction_id }}">View Details</button>
                                            <div id="myModal{{ $transaksi->transaction_id }}" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel{{ $transaksi->transaction_id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header"><h5 class="modal-title" id="myModalLabel{{ $transaksi->transaction_id }}">Detail Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                                        <div class="modal-body">
                                                            <p><strong>Receipt Code : </strong> {{ $transaksi->receipt_code }}</p>
                                                            <p><strong>Customer Name : </strong> {{ $transaksi->customer_name }}</p>
                                                            <p><strong>Booking Date & Time : </strong> @if($transaksi->booking) {{ \Carbon\Carbon::parse($transaksi->booking->booking_datetime)->format('d M Y, H:i A') }} @else N/A @endif</p>
                                                            <p><strong>Status Pembayaran : </strong> <span style="text-transform: capitalize;">{{ $transaksi->status }}</span></p>
                                                            <hr><h6>Products:</h6>
                                                            @if($transaksi->products->isNotEmpty())<ul>@foreach($transaksi->products as $product)<li>{{ $product->name }} - Rp {{ number_format($product->price, 0, ',', '.') }}</li>@endforeach</ul>
                                                            @else <p>No products associated.</p> @endif
                                                            <hr>
                                                            <p><strong>Temporary Link : </strong> @if($transaksi->temporary_link) <a href="{{ $transaksi->temporary_link }}" target="_blank">{{ $transaksi->temporary_link }}</a> @else N/A @endif</p>
                                                            <p><strong>Selected Photos : </strong> @if($transaksi->selected_photos) <a href="{{ $transaksi->selected_photos }}" target="_blank">{{ $transaksi->selected_photos }}</a> @else N/A @endif</p>
                                                            <p><strong>Final Link : </strong> @if($transaksi->final_link) <a href="{{ $transaksi->final_link }}" target="_blank">{{ $transaksi->final_link }}</a> @else N/A @endif</p>
                                                            <p><strong>Status Transaksi:</strong> {{ $transaksi->isActive ? 'Active' : 'Inactive' }}</p>
                                                        </div>
                                                        <div class="modal-footer"><button type="button" class="btn btn-light waves-effect" data-bs-dismiss="modal">Close</button></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('transaksi.edit', $transaksi->transaction_id) }}" class="px-2 text-primary" title="Edit"><i class="uil uil-pen font-size-18"></i></a>
                                            <form action="{{ route('transaksi.destroy', $transaksi->transaction_id) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete"><i class="uil uil-trash-alt font-size-18"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">No transactions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="row mt-4">
                        <div class="col-sm-6"><div><p class="mb-sm-0">Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} entries</p></div></div>
                        <div class="col-sm-6"><div class="float-sm-end">{{ $transactions->appends(request()->except('page'))->links() }}</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    {{-- <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script> --}}
    {{-- <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script> --}}
    @if(session('success')) <script> alert("{{ session('success') }}"); </script> @endif
    @if(session('error')) <script> alert("{{ session('error') }}"); </script> @endif
@endsection
