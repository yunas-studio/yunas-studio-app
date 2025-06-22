<?php

namespace App\Http\Controllers;

use App\Models\Packet;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PacketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Packet::with('product')->latest();
        
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        
        $packets = $query->get();
        $products = Product::all();
        
        return view('packets.index', compact('packets', 'products'));
    }

    /**
     * Display packets by product.
     */
    public function product($id)
    {
        $product = Product::findOrFail($id);
        $packets = Packet::where('product_id', $product->id)->latest()->get();
        $products = Product::all();
        
        return view('packets.index', compact('packets', 'products', 'product'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();
        return view('packets.create', compact('products'));
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
            'product_id' => 'required|exists:products,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/packets', $imageName);
            $data['image'] = 'packets/' . $imageName;
        }

        Packet::create($data);

        return redirect()->route('packets.index')
                         ->with('success', 'Packet created successfully :)');
    }

    /**
     * Display the specified resource.
     */
    public function show(Packet $packet)
    {
        $packet->load(['product', 'additionalDefaults.additional']);
        return view('packets.show', compact('packet'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Packet $packet)
    {
        $products = Product::all();
        return view('packets.edit', compact('packet', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Packet $packet)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'product_id' => 'required|exists:products,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($packet->image) {
                Storage::delete('public/' . $packet->image);
            }
            
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/packets', $imageName);
            $data['image'] = 'packets/' . $imageName;
        }

        $packet->update($data);

        return redirect()->route('packets.index')
                         ->with('success', 'Packet updated successfully :)');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Packet $packet)
    {
        // Delete image if exists
        if ($packet->image) {
            Storage::delete('public/' . $packet->image);
        }
        
        $packet->delete();

        return redirect()->route('packets.index')
                         ->with('success', 'Packet deleted successfully :(');
    }
    
    /**
     * Toggle the status of the packet.
     */
    public function toggleStatus(Packet $packet)
    {
        $packet->update([
            'is_active' => !$packet->is_active
        ]);
        
        $status = $packet->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
                         ->with('success');
    }
}
