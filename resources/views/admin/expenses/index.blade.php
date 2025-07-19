@extends('layouts.master')

@section('title')
    Expenses
@endsection

@section('css')
    <style>
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
                            @if($month)
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
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card mini-stats-wid">
                                                <div class="card-body">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <p class="text-muted fw-medium">Total Expenses</p>
                                                            <h4 class="mb-0">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h4>
                                                        </div>
                                                        <div>
                                                            <i class="bx bx-money font-size-24 text-primary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Expenses by Category</h5>
                                    
                                    @if(isset($expensesByCategory) && count($expensesByCategory) > 0)
                                        @foreach($expensesByCategory->take(3) as $category)
                                            <div class="mb-3">
                                                <p class="mb-1 d-flex justify-content-between">
                                                    <span>{{ $category->category }}</span>
                                                    <span>Rp {{ number_format($category->total, 0, ',', '.') }}</span>
                                                </p>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                        style="width: {{ ($category->total / $totalExpenses) * 100 }}%" 
                                                        aria-valuenow="{{ ($category->total / $totalExpenses) * 100 }}" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No category data available</p>
                                    @endif
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
                                        <div class="col-md-4">
                                            <label for="month" class="form-label">Month</label>
                                            <select name="month" id="month" class="form-select">
                                                <option value="">All Months</option>
                                                @foreach($months as $key => $monthName)
                                                    <option value="{{ $key }}" {{ request('month') == $key ? 'selected' : '' }}>{{ $monthName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="year" class="form-label">Year</label>
                                            <select name="year" id="year" class="form-select">
                                                <option value="" {{ request('year') === null ? 'selected' : '' }}>All Years</option>
                                                @foreach($years as $yearOption)
                                                    <option value="{{ $yearOption }}" {{ request('year') == $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
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
                                    <th>Number</th>
                                    <th>Name</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                    <tr>
                                        <td>{{ $expense->number }}</td>
                                        <td>{{ $expense->name }}</td>
                                        <td>{{ $expense->formatted_amount }}</td>
                                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                        <td>{{ $expense->category ?? '-' }}</td>
                                        <td>{{ $expense->keterangan ?? '-' }}</td>
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