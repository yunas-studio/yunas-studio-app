<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ProductCategory::latest()->get();
        return view('product-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $imageName);
            $data['image'] = 'categories/' . $imageName;
        }

        ProductCategory::create($data);

        return redirect()->route('product-categories.index')
                         ->with('success', 'Category created successfully :)');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        return view('product-categories.show', compact('productCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductCategory $productCategory)
    {
        return view('product-categories.edit', compact('productCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($productCategory->image) {
                Storage::delete('public/' . $productCategory->image);
            }
            
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $imageName);
            $data['image'] = 'categories/' . $imageName;
        }

        $productCategory->update($data);

        return redirect()->route('product-categories.index')
                         ->with('success', 'Category updated successfully :)');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        // Check if category has products
        if ($productCategory->products()->count() > 0) {
            return redirect()->route('product-categories.index')
                             ->with('error', 'Cannot delete category with products :)');
        }

        // Delete image if exists
        if ($productCategory->image) {
            Storage::delete('public/' . $productCategory->image);
        }
        
        $productCategory->delete();

        return redirect()->route('product-categories.index')
                         ->with('success', 'Category deleted successfully :(');
    }
}
