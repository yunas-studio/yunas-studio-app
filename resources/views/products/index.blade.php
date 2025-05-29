@extends('layouts.master')

@section('title')
    @if(isset($category))
        {{ $category->name }} - Products
    @else
        Products
    @endif
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products @endslot
        @slot('title') 
            @if(isset($category))
                {{ $category->name }}
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
                        @foreach($categories as $cat)
                            <a href="{{ route('products.category', $cat->id) }}" class="list-group-item list-group-item-action {{ isset($category) && $category->id == $cat->id ? 'active' : '' }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                    
                    @auth
                        <div class="mt-4">
                            <a href="{{ route('product-categories.index') }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bx bx-cog me-1"></i> Manage Categories
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            @if(isset($category))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            @if($category->image)
                                <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" class="img-fluid rounded" style="max-height: 120px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 120px;">
                                    <i class="bx bx-image-alt text-secondary" style="font-size: 3rem;"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <h4 class="card-title">{{ $category->name }}</h4>
                            <p class="text-muted">
                                @if($category->description)
                                    {{ $category->description }}
                                @else
                                    <span class="fst-italic">No description available</span>
                                @endif
                            </p>
                            <div class="badge bg-info text-white">
                                <i class="bx bx-package me-1"></i> {{ $products->count() }} Products
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">
                            @if(isset($category))
                                {{ $category->name }} Products
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

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <!-- <th>Image</th> -->
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr class="{{ $product->is_active ? '' : 'table-secondary' }}">
                                        <td>{{ $product->id }}</td>
                                        <!-- <td>
                                            @if($product->image)
                                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                                    <i class="bx bx-image-alt text-secondary"></i>
                                                </div>
                                            @endif
                                        </td> -->
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->category ? $product->category->name : 'Uncategorized' }}</td>
                                        <td class="text-primary fw-bold">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                        <td>
                                            @if($product->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @auth
                                                <form action="{{ route('products.toggle-status', $product->id) }}" method="POST" class="me-1">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-{{ $product->is_active ? 'warning' : 'success' }}">
                                                        <i class="bx bx-power-off"></i>
                                                    </button>
                                                </form>
                                                @endauth
                                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('delete this product?');">
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
                                            No products found. <a href="{{ route('products.create') }}" class="alert-link">Add your product</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 