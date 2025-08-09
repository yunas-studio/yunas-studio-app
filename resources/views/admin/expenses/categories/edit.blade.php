@extends('layouts.master')

@section('title')
    Edit Expense Category
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Edit Expense Category @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Edit Expense Category</h4>
                    
                    <form action="{{ route('expense-categories.update', $expenseCategory->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $expenseCategory->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_monthly_default" value="0">
                            <input type="checkbox" class="form-check-input" id="is_monthly_default" name="is_monthly_default" value="1" {{ old('is_monthly_default', $expenseCategory->is_monthly_default) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_monthly_default">Set as Monthly Default</label>
                            <div class="form-text">If checked, this category will be automatically generated each month.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('expense-categories.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection