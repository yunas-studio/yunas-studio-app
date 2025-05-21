<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::latest();
        
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        
        $products = $query->get();
        return view('products.index', compact('products'));
    }

    /**
     * Display products by category.
     */
    public function category($category)
    {
        // Convert URL-friendly category format back to original format if needed
        $categoryMap = [
            'PaketPersonal' => 'Paket Personal',
            'PaketCouple' => 'Paket Couple',
            'PaketFamily' => 'Paket Family',
            'PaketGrup' => 'Paket Grup',
            'PaketGraduation' => 'Paket Graduation',
            'PaketMaternity' => 'Paket Maternity',
            'PaketPrawedding' => 'Paket Prawedding',
            'PasPhoto' => 'Pas Photo',
            'RentalStudio' => 'Rental Studio'
        ];
        
        $categoryName = $categoryMap[$category] ?? $category;
        
        $products = Product::where('category', $categoryName)->latest()->get();
        return view('products.index', compact('products', 'category', 'categoryName'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
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
            'category' => 'required|string|max:255',
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
                         ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
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
            'category' => 'required|string|max:255',
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
                         ->with('success', 'Product updated successfully.');
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
                         ->with('success', 'Product deleted successfully.');
    }
}
