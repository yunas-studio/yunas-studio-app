<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->latest();
        
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        $products = $query->get();
        $categories = ProductCategory::all();
        
        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Display products by category.
     */
    public function category($id)
    {
        $category = ProductCategory::findOrFail($id);
        $products = Product::where('category_id', $category->id)->latest()->get();
        $categories = ProductCategory::all();
        
        return view('products.index', compact('products', 'categories', 'category'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ProductCategory::all();
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:product_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/products', $imageName);
            $data['image'] = 'products/' . $imageName;
        }

        Product::create($data);

        return redirect()->route('products.index')
                         ->with('success', 'Product created successfully :)');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('category');
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = ProductCategory::all();
        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:product_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::delete('public/' . $product->image);
            }
            
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/products', $imageName);
            $data['image'] = 'products/' . $imageName;
        }

        $product->update($data);

        return redirect()->route('products.index')
                         ->with('success', 'Product updated successfully :)');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image) {
            Storage::delete('public/' . $product->image);
        }
        
        $product->delete();

        return redirect()->route('products.index')
                         ->with('success', 'Product deleted successfully :(');
    }
    
    /**
     * Toggle the status of the product.
     */
    public function toggleStatus(Product $product)
    {
        $product->update([
            'is_active' => !$product->is_active
        ]);
        
        $status = $product->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
                         ->with('success');
        // return redirect()->back()
        //                  ->with('success', "Product {$product->name} has been {$status}.");
    }
}
