<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\Packet;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $belumDibayarCount = Transaksi::where('status', 'belum dibayar')->count();
        $dpCount = Transaksi::where('status', 'dp')->count();
        $sudahDibayarCount = Transaksi::where('status', 'sudah dibayar')->count();
        $search = request('search');

        // Eager load all necessary relationships for the index page
        $transactions = Transaksi::with(['packet.product', 'additionals'])
            ->when($search, function ($query) use ($search) {
                return $query->where('receipt_code', 'like', "%$search%")
                      ->orWhere('customer_name', 'like', "%$search%");
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.transaction.transaksi', compact('transactions', 'belumDibayarCount', 'dpCount', 'sudahDibayarCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $packets = Packet::with('product')
            ->where('is_active', 1)
            ->get()
            ->groupBy('product.name');

        $all_additionals = Additional::orderBy('name')->get();

        return view('admin.transaction.create', compact('packets', 'all_additionals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name'   => ['required', 'string', 'max:50'],
            'status'          => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'packet_id'       => ['required', 'exists:packets,id'],
            'additionals'     => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'temporary_link'  => ['nullable', 'url', 'max:255'],
            'selected_photos' => ['nullable', 'url', 'max:255'],
            'final_link'      => ['nullable', 'url', 'max:255'],
            'note'            => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            // Calculate price from additionals
            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $details) {
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }

            $totalPrice = $subtotal - $discount;

            $transaksi = Transaksi::create([
                'customer_name'   => $validatedData['customer_name'],
                'status'          => $validatedData['status'],
                'packet_id'       => $validatedData['packet_id'],
                'process_status'  => 'Siap Cetak',
                'receipt_code'    => 'TEMP-' . uniqid(),
                'total_price'     => $totalPrice < 0 ? 0 : $totalPrice,
                'discount'        => $discount,
                'temporary_link'  => $validatedData['temporary_link'],
                'selected_photos' => $validatedData['selected_photos'],
                'final_link'      => $validatedData['final_link'],
                'note'            => $validatedData['note'],
            ]);

            // Sync additionals
            if (!empty($validatedData['additionals'])) {
                $syncData = [];
                foreach ($validatedData['additionals'] as $id => $details) {
                    $syncData[$id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                }
                $transaksi->additionals()->sync($syncData);
            }

            // Update receipt code
            $transaksi->receipt_code = "INV/" . Carbon::now()->format('Ymd') . "/" . $transaksi->transaction_id;
            $transaksi->save();

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create transaction.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $transaksi = Transaksi::with(['packet', 'additionals'])->findOrFail($id);
        
        $packets = Packet::with('product')
            ->where('is_active', 1)
            ->get()
            ->groupBy('product.name');

        $all_additionals = Additional::orderBy('name')->get();
        
        return view('admin.transaction.edit', compact('transaksi', 'packets', 'all_additionals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        
        $validatedData = $request->validate([
            'customer_name' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'process_status' => ['required', Rule::in(['Siap Cetak', 'Proses Cetak', 'Selesai'])],
            'packet_id' => ['required', 'exists:packets,id'],
            'additionals' => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'temporary_link' => ['nullable', 'url', 'max:255'],
            'selected_photos' => ['nullable', 'url', 'max:255'],
            'final_link' => ['nullable', 'url', 'max:255'],
            'note'            => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            // Sync additionals and calculate their price
            $syncData = [];
            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $additional_id => $details) {
                    $syncData[$additional_id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }
            $transaksi->additionals()->sync($syncData);

            $totalPrice = $subtotal - $discount;

            // Update main transaction fields
            $transaksi->update([
                'customer_name'   => $validatedData['customer_name'],
                'status'          => $validatedData['status'],
                'process_status'  => $validatedData['process_status'],
                'packet_id'       => $validatedData['packet_id'],
                'total_price'     => $totalPrice < 0 ? 0 : $totalPrice,
                'discount'        => $discount,
                'temporary_link'  => $validatedData['temporary_link'],
                'selected_photos' => $validatedData['selected_photos'],
                'final_link'      => $validatedData['final_link'],
                'note'            => $validatedData['note'],
            ]);

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->delete();
        return redirect()->route('transaksi.index')->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Handle inline status updates from the index page.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'field' => ['required', Rule::in(['status', 'process_status'])],
            'value' => ['required', 'string'],
        ]);

        $transaksi = Transaksi::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        if ($field === 'status' && !in_array($value, ['belum dibayar', 'dp', 'sudah dibayar'])) {
            return redirect()->back()->with('error', 'Invalid payment status value.');
        } elseif ($field === 'process_status' && !in_array($value, ['Siap Cetak', 'Proses Cetak', 'Selesai'])) {
            return redirect()->back()->with('error', 'Invalid process status value.');
        }

        $transaksi->{$field} = $value;
        $transaksi->save();

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    /**
     * Get the default additionals for a given packet via AJAX.
     */
    public function getDefaultAdditionals(Packet $packet)
    {
        $defaults = $packet->additionalDefaults()->with('additional')->get();
        return response()->json($defaults);
    }
}
