@extends('layouts.master')
@section('title') @lang('translation.Dashboard') @endsection
@section('content')
@component('common-components.breadcrumb')
@slot('pagetitle') Minible @endslot
@slot('title') Dashboard @endslot
@endcomponent


<div id="monthly-expenses-data" 
    data-expenses="{{ json_encode($monthlyExpenses) }}"
    data-income="{{ json_encode($monthlyIncome) }}"
    data-labels="{{ json_encode($chartLabels ?? []) }}"
    data-period="{{ $chartType ?? 'monthly' }}"
    style="display: none;"></div>

<div class="row">
    
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <div class="avatar-sm">
                        <span class="avatar-title bg-soft-success text-success font-size-24 rounded-circle">
                            <i class="mdi mdi-cash-multiple"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <h4 class="mb-1 mt-1">Rp <span data-plugin="counterup">{{ number_format($totalIncome, 0, ',', '.') }}</span></h4>
                    <p class="text-muted mb-0">Total Pemasukan</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <a href="{{ route('transaksi.index') }}" class="text-reset">Lihat Detail <i class="mdi mdi-arrow-right ms-1"></i></a>
                </p>
            </div>
        </div>
    </div> 

    
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <div class="avatar-sm">
                        <span class="avatar-title bg-soft-danger text-danger font-size-24 rounded-circle">
                            <i class="mdi mdi-cash-minus"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <h4 class="mb-1 mt-1">Rp <span data-plugin="counterup">{{ number_format($totalExpenses, 0, ',', '.') }}</span></h4>
                    <p class="text-muted mb-0">Total Pengeluaran</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <a href="{{ route('expenses.index') }}" class="text-reset">Lihat Detail <i class="mdi mdi-arrow-right ms-1"></i></a>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <div class="avatar-sm">
                        <span class="avatar-title bg-soft-primary text-primary font-size-24 rounded-circle">
                            <i class="mdi mdi-account-group"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $totalCustomers }}</span></h4>
                    <p class="text-muted mb-0">Total Konsumen</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <span class="text-muted">Konsumen unik terdaftar</span>
                </p>
            </div>
        </div>
    </div> <!-- end col-->
</div> <!-- end row-->

<div class="row">
    <div class="col-md-12">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h3 class="text-white mb-1">Rp <span data-plugin="counterup">{{ number_format($currentBalance, 0, ',', '.') }}</span></h3>
                        <p class="text-white-50 mb-0">Saldo Saat Ini</p>
                    </div>
                    <div class="col-4 text-end">
                        <div class="avatar-lg">
                            <span class="avatar-title bg-white bg-opacity-25 text-white font-size-32 rounded-circle">
                                <i class="mdi mdi-wallet"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="float-end">
                    
                </div>
                <h4 class="card-title mb-4">Grafik Pemasukan & Pengeluaran</h4>

                <div class="mt-1">
                    <ul class="list-inline main-chart mb-0">
                        <li class="list-inline-item chart-border-left me-0 border-0">
                            <h3 class="text-success">Rp <span data-plugin="counterup">{{ number_format($totalIncome, 0, ',', '.') }}</span><span class="text-muted d-inline-block font-size-15 ms-3">Total Pemasukan</span></h3>
                        </li>
                        <li class="list-inline-item chart-border-left me-0">
                            <h3 class="text-danger">Rp <span data-plugin="counterup">{{ number_format($totalExpenses, 0, ',', '.') }}</span><span class="text-muted d-inline-block font-size-15 ms-3">Total Pengeluaran</span></h3>
                        </li>
                    </ul>
                </div>

                <div class="float-end mt-2">
                    <div class="dropdown">
                        <a class="dropdown-toggle text-reset" href="#" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="fw-semibold">Sort By:</span> <span class="text-muted">{{ ucfirst($period ?? 'monthly') }}<i class="mdi mdi-chevron-down ms-1"></i></span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                            @if(!auth()->user()->isKasir())
                                <a class="dropdown-item {{ ($period ?? 'monthly') == 'daily' ? 'active' : '' }}" href="{{ route('dashboard', ['period' => 'daily']) }}">Daily</a>
                                <a class="dropdown-item {{ ($period ?? 'monthly') == 'monthly' ? 'active' : '' }}" href="{{ route('dashboard', ['period' => 'monthly']) }}">Monthly</a>
                                <a class="dropdown-item {{ ($period ?? 'monthly') == 'yearly' ? 'active' : '' }}" href="{{ route('dashboard', ['period' => 'yearly']) }}">Yearly</a>
                            @else
                                <a class="dropdown-item active" href="#">Daily</a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div id="monthly-expenses-chart" data-colors='["--bs-danger", "#dfe2e6", "--bs-warning"]' class="apex-charts" dir="ltr"></div>
                </div>
            </div> 
        </div> 
    </div> 

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Expenses by Category</h4>

                @if(count($expensesByCategory) > 0)
                    @foreach($expensesByCategory as $category)
                        <div class="row align-items-center g-0 mt-3">
                            <div class="col-sm-5">
                                <p class="text-truncate mt-1 mb-0"><i class="mdi mdi-circle-medium text-primary me-2"></i> {{ $category->category_name }} </p>
                            </div>
                            <div class="col-sm-7">
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar progress-bar bg-primary" role="progressbar"
                                        style="width: {{ $totalExpenses > 0 ? ($category->total / $totalExpenses) * 100 : 0 }}%" 
                                        aria-valuenow="{{ $totalExpenses > 0 ? ($category->total / $totalExpenses) * 100 : 0 }}" 
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <p class="text-end mb-0 mt-1">Rp {{ number_format($category->total, 0, ',', '.') }}</p>
                            </div>
                        </div> 
                    @endforeach
                @else
                    <p class="text-muted">No category data available</p>
                @endif

                <div class="mt-4 text-center">
                    <a href="{{ route('expenses.index') }}" class="btn btn-primary btn-sm">View All Expenses</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="float-end">
                    <div class="dropdown">
                        <a class="dropdown-toggle text-reset" href="#" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="fw-semibold">Sort By:</span> <span class="text-muted">Yearly<i class="mdi mdi-chevron-down ms-1"></i></span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton5">
                            <a class="dropdown-item" href="#">Monthly</a>
                            <a class="dropdown-item" href="#">Yearly</a>
                            <a class="dropdown-item" href="#">Weekly</a>
                        </div>
                    </div>
                </div>
            </div> 
        </div> 
    </div> 
</div> <!-- end row-->
<!-- end row -->

@endsection
@section('script')
<!-- apexcharts -->
<script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

<script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>
<script src="{{ URL::asset('/assets/js/pages/monthly-expenses.init.js') }}"></script>
@endsection
