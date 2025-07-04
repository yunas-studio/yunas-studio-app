@extends('layouts.master')

@section('title')
    Expenses
@endsection

@section('css')
    <style>
        /* Row styling for paid/unpaid expenses */
        .row-danger {
            background-color: rgba(244, 106, 106, 0.2) !important;
        }
        .row-success {
            background-color: rgba(52, 195, 143, 0.2) !important;
        }
        
        /* Status badge styling */
        .status-badge-lunas {
            background-color: #34c38f !important;
            color: #fff !important;
            border-color: #34c38f !important;
            font-weight: 600;
        }
        .status-badge-belum_lunas {
            background-color: #f46a6a !important;
            color: #fff !important;
            border-color: #f46a6a !important;
            font-weight: 600;
        }
        
        /* Dropdown styling */
        .btn-group .dropdown-menu {
            min-width: 6rem;
        }
        .btn-group .dropdown-item.active {
            background-color: #f8f9fa;
            color: #495057;
        }
        .btn-sm.dropdown-toggle {
            padding: 0.25rem 0.5rem;
            font-size: 0.76563rem;
            border-radius: 0.2rem;
        }
        
        /* Filter status indicators */
        .filter-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .filter-status-lunas {
            background-color: rgba(52, 195, 143, 0.2);
            color: #34c38f;
            border: 1px solid #34c38f;
        }
        .filter-status-belum_lunas {
            background-color: rgba(244, 106, 106, 0.2);
            color: #f46a6a;
            border: 1px solid #f46a6a;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Expenses @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title">Expenses List</h4>
                            @if($status)
                                <div class="mt-2">
                                    <span class="filter-status filter-status-{{ $status }}">
                                        <i class="bx {{ $status == 'lunas' ? 'bx-check-circle' : 'bx-x-circle' }} me-1"></i>
                                        {{ $status == 'lunas' ? 'Paid Only' : 'Unpaid Only' }}
                                    </span>
                                    @if($month)
                                        <span class="filter-status">
                                            <i class="bx bx-calendar me-1"></i>
                                            {{ $months[$month] }} {{ $year }}
                                        </span>
                                    @elseif($year)
                                        <span class="filter-status">
                                            <i class="bx bx-calendar me-1"></i>
                                            {{ $year }}
                                        </span>
                                    @else
                                        <span class="filter-status">
                                            <i class="bx bx-calendar me-1"></i>
                                            All Years
                                        </span>
                                    @endif
                                </div>
                            @elseif($month)
                                <div class="mt-2">
                                    <span class="filter-status">
                                        <i class="bx bx-calendar me-1"></i>
                                        {{ $months[$month] }} {{ $year }}
                                    </span>
                                </div>
                            @elseif($year != date('Y'))
                                <div class="mt-2">
                                    <span class="filter-status">
                                        <i class="bx bx-calendar me-1"></i>
                                        {{ $year }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add New Expense
                        </a>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card mini-stats-wid">
                                                <div class="card-body">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <p class="text-muted fw-medium">Total Expenses</p>
                                                            <h4 class="mb-0">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h4>
                                                        </div>
                                                                                                <div>
                                            <!-- <span class="avatar-title bg-primary">
                                                <i class="bx bx-money font-size-24"></i>
                                            </span> -->
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
                                                            <p class="text-muted fw-medium">Paid Expenses</p>
                                                            <h4 class="mb-0">Rp {{ number_format($totalLunas, 0, ',', '.') }}</h4>
                                                        </div>
                                                                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-success align-self-center">
                                            <span class="avatar-title bg-success">
                                                <i class="bx bx-check-circle font-size-24"></i>
                                            </span>
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
                                                            <p class="text-muted fw-medium">Unpaid Expenses</p>
                                                            <h4 class="mb-0">Rp {{ number_format($totalBelumLunas, 0, ',', '.') }}</h4>
                                                        </div>
                                                                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-danger align-self-center">
                                            <span class="avatar-title bg-danger">
                                                <i class="bx bx-x-circle font-size-24"></i>
                                            </span>
                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Filter Expenses</h5>
                                    <form action="{{ route('expenses.index') }}" method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="statuss" class="form-label">Status</label>
                                            <select name="statuss" id="statuss" class="form-select">
                                                <option value="">All Status</option>
                                                <option value="lunas" {{ request('statuss') == 'lunas' ? 'selected' : '' }}>Paid</option>
                                                <option value="belum_lunas" {{ request('statuss') == 'belum_lunas' ? 'selected' : '' }}>Unpaid</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="month" class="form-label">Month</label>
                                            <select name="month" id="month" class="form-select">
                                                <option value="">All Months</option>
                                                @foreach($months as $key => $monthName)
                                                    <option value="{{ $key }}" {{ request('month') == $key ? 'selected' : '' }}>{{ $monthName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="year" class="form-label">Year</label>
                                            <select name="year" id="year" class="form-select">
                                                <option value="" {{ request('year') === null ? 'selected' : '' }}>All Years</option>
                                                @foreach($years as $yearOption)
                                                    <option value="{{ $yearOption }}" {{ request('year') == $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-filter-alt me-1"></i> Apply Filter
                                            </button>
                                            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                                                <i class="bx bx-reset me-1"></i> Reset All Filters
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                    <tr class="{{ $expense->status == 'belum_lunas' ? 'row-danger' : 'row-success' }}">
                                        <td>{{ $expense->id }}</td>
                                        <td>{{ $expense->name }}</td>
                                        <td>{{ $expense->formatted_amount }}</td>
                                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                        <td>{{ $expense->category ?? '-' }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm status-badge-{{ $expense->status }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bx {{ $expense->status == 'lunas' ? 'bx-check-circle' : 'bx-x-circle' }} me-1"></i>
                                                    {{ $expense->status == 'lunas' ? 'Paid' : 'Unpaid' }}
                                                </button>
                                                <div class="dropdown-menu">
                                                    <form action="{{ route('expenses.update-status', $expense->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="lunas">
                                                        <button type="submit" class="dropdown-item {{ $expense->status == 'lunas' ? 'active' : '' }}">
                                                            <i class="bx bx-check-circle me-1 text-success"></i> Paid
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('expenses.update-status', $expense->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="belum_lunas">
                                                        <button type="submit" class="dropdown-item {{ $expense->status == 'belum_lunas' ? 'active' : '' }}">
                                                            <i class="bx bx-x-circle me-1 text-danger"></i> Unpaid
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('expenses.show', $expense->id) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            No expenses found. <a href="{{ route('expenses.create') }}" class="alert-link">Add your first expense</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $expenses->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 