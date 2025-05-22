@extends('layouts.master')

@section('title')
    {{ $product->name }} - Product Details
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products @endslot
        @slot('title') Product Details @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Product Details</h4>
                        <div>
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary me-2">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="product-img-box text-center mb-4">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 400px;">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                                        <i class="bx bx-image-alt text-secondary" style="font-size: 5rem;"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="product-detail-box">
                                <h3 class="product-title mb-2">{{ $product->name }}</h3>
                                <div class="product-category badge bg-light text-dark mb-3">
                                    {{ $product->category }}
                                </div>
                                <h4 class="product-price text-primary mb-4">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </h4>
                                
                                <div class="product-description mb-4">
                                    <h5>Description</h5>
                                    <p class="text-muted">
                                        {!! nl2br(e($product->description)) !!}
                                    </p>
                                </div>
                                
                                <div class="product-meta mb-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                Created: {{ $product->created_at->format('d M Y, H:i') }}
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <small class="text-muted">
                                                Last Updated: {{ $product->updated_at->format('d M Y, H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="product-actions mt-4">
                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bx bx-trash"></i> Delete Product
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 