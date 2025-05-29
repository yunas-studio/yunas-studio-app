@extends('layouts.master')

@section('title')
    {{ $productCategory->name }} - Category Details
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products @endslot
        @slot('title') Category Details @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Category Information</h4>
                    
                    <div class="text-center mb-4">
                        @if($productCategory->image)
                            <img src="{{ Storage::url($productCategory->image) }}" alt="{{ $productCategory->name }}" class="img-fluid rounded" style="max-height: 200px;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 200px;">
                                <i class="bx bx-image-alt text-secondary" style="font-size: 4rem;"></i>
                            </div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <h5 class="text-primary">{{ $productCategory->name }}</h5>
                    </div>
                    
                    @if($productCategory->description)
                        <div class="mb-4">
                            <h6>Description:</h6>
                            <p>{{ $productCategory->description }}</p>
                        </div>
                    @endif
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('product-categories.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Categories
                        </a>
                        <a href="{{ route('product-categories.edit', $productCategory->id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Edit Category
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Products in this Category</h4>
                        <a href="{{ route('products.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add New Product
                        </a>
                    </div>
                    
                    <div class="row">
                        @forelse($productCategory->products as $product)
                            <div class="col-md-6 mb-4">
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
                                        <p class="card-text fw-bold text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                        <div class="d-flex justify-content-between mt-3">
                                            <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bx bx-edit"></i> Edit
                                            </a>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('delete this product?');">
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
                                    No products found in this category. <a href="{{ route('products.create') }}" class="alert-link">Add your product</a>.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 