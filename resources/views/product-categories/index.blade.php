@extends('layouts.master')

@section('title')
    Product Categories
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products @endslot
        @slot('title') Product Categories @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Product Categories</h4>
                        <a href="{{ route('product-categories.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add New Category
                        </a>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
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
                                    <th>Description</th>
                                    <th>Products Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <!-- <td>
                                            @if($category->image)
                                                <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                                    <i class="bx bx-image-alt text-secondary"></i>
                                                </div>
                                            @endif
                                        </td> -->
                                        <td>{{ $category->name }}</td>
                                        <td>
                                            @if($category->description)
                                                {{ \Str::limit($category->description, 50) }}
                                            @else
                                                <span class="text-muted">No description</span>
                                            @endif
                                        </td>
                                        <td>{{ $category->products->count() }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('products.category', $category->id) }}" class="btn btn-sm btn-info me-2">
                                                    <i class="bx bx-show"></i> View Products
                                                </a>
                                                <a href="{{ route('product-categories.edit', $category->id) }}" class="btn btn-sm btn-primary me-2">
                                                    <i class="bx bx-edit"></i> Edit
                                                </a>
                                                <form action="{{ route('product-categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('delete this category?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bx bx-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No categories found.</td>
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