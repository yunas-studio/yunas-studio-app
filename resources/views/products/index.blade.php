@extends('layouts.master')

@section('title')
    @if(isset($categoryName))
        {{ $categoryName }} - Products
    @else
        Products
    @endif
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products @endslot
        @slot('title') 
            @if(isset($categoryName))
                {{ $categoryName }}
            @else
                Products List
            @endif
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Product Categories</h4>
                    
                    <div class="list-group">
                        <a href="{{ route('products.index') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.index' ? 'active' : '' }}">
                            All Products
                        </a>
                        <a href="{{ route('products.category', 'PaketPersonal') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketPersonal' ? 'active' : '' }}">
                            Paket Personal
                        </a>
                        <a href="{{ route('products.category', 'PaketCouple') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketCouple' ? 'active' : '' }}">
                            Paket Couple
                        </a>
                        <a href="{{ route('products.category', 'PaketFamily') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketFamily' ? 'active' : '' }}">
                            Paket Family
                        </a>
                        <a href="{{ route('products.category', 'PaketGrup') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketGrup' ? 'active' : '' }}">
                            Paket Grup
                        </a>
                        <a href="{{ route('products.category', 'PaketGraduation') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketGraduation' ? 'active' : '' }}">
                            Paket Graduation
                        </a>
                        <a href="{{ route('products.category', 'PaketMaternity') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketMaternity' ? 'active' : '' }}">
                            Paket Maternity
                        </a>
                        <a href="{{ route('products.category', 'PaketPrawedding') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PaketPrawedding' ? 'active' : '' }}">
                            Paket Prawedding
                        </a>
                        <a href="{{ route('products.category', 'PasPhoto') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'PasPhoto' ? 'active' : '' }}">
                            Pas Photo
                        </a>
                        <a href="{{ route('products.category', 'RentalStudio') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'products.category' && request()->route('category') == 'RentalStudio' ? 'active' : '' }}">
                            Rental Studio
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">
                            @if(isset($categoryName))
                                {{ $categoryName }} Products
                            @else
                                Products List
                            @endif
                        </h4>
                        <a href="{{ route('products.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add New Product
                        </a>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row">
                        @forelse($products as $product)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                                    @else
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="bx bx-image-alt text-secondary" style="font-size: 3rem;"></i>
                                        </div>
                                    @endif
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $product->name }}</h5>
                                        <p class="card-text text-muted">{{ $product->category }}</p>
                                        <p class="card-text fw-bold text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                        <div class="d-flex justify-content-between mt-3">
                                            <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bx bx-edit"></i> Edit
                                            </a>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bx bx-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    No products found. <a href="{{ route('products.create') }}" class="alert-link">Add your product</a>.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 