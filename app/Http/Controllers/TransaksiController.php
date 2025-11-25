<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\Packet;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TransaksiController extends Controller
{
    // Definisi Status Baru secara Global agar konsisten
    private $processStatuses = [
        'Pelanggan Belum Foto',
        'Pelanggan Pilih Foto',
        'Siap Edit dan Cetak',
        'Proses Edit dan Cetak',
        'Selesai'
    ];

    public function index(Request $request)
    {
        $query = Transaksi::with(['packet.product', 'packet.printOptions', 'user', 'additionals']);
        $user = auth()->user();
        
        if ($user->isUser()) {
            $query->where('phone_number', $user->username);
        }
        
        $search = $request->input('search');
        $query->when($search, function ($q) use ($search) {
            $q->where(function($sub) use ($search) {
                $sub->where('receipt_code', 'like', "%$search%")
                    ->orWhere('customer_name', 'like', "%$search%");
            });
        })
        ->when($request->filled('payment_status'), function ($q) use ($request) {
            $q->where('status', $request->payment_status);
        })
        ->when($request->filled('process_status'), function ($q) use ($request) {
            $q->where('process_status', $request->process_status);
        })
        ->when($request->filled('packet_id'), function ($q) use ($request) {
            $q->where('packet_id', $request->packet_id);
        })
        ->when($request->filled('start_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->start_date);
        })
        ->when($request->filled('end_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->end_date);
        });

        $financialQuery = clone $query;

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortBy, ['total_price', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPageOptions = [5, 10, 20, 50, 100];
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 10;
        }
        $transactions = $query->paginate($perPage)->withQueryString();
        
        // Kirim variabel status baru ke View
        $processStatuses = $this->processStatuses;
        $paymentStatuses = ['belum dibayar', 'dp', 'sudah dibayar'];

        if ($user->isUser()) {
            $userPacketIds = (clone $financialQuery)->distinct()->pluck('packet_id');
            $packetsForFilter = Packet::with('product')->whereIn('id', $userPacketIds)->orderBy('name')->get()->groupBy('product.name');
            
            return view('user.transaction.transaksi', compact(
                'transactions', 'packetsForFilter', 'paymentStatuses', 'processStatuses', 'perPageOptions'
            ));
        } else {
             $totalBelumDibayar = (clone $financialQuery)->where('status', 'belum dibayar')->sum('total_price');
             $countBelumDibayar = (clone $financialQuery)->where('status', 'belum dibayar')->count();
             $totalDpPaid = (clone $financialQuery)->where('status', 'dp')->sum('dp_amount');
             $countDp = (clone $financialQuery)->where('status', 'dp')->count();
             $totalSudahDibayar = (clone $financialQuery)->where('status', 'sudah dibayar')->sum('total_price');
             $countSudahDibayar = (clone $financialQuery)->where('status', 'sudah dibayar')->count();
             $totalProfit = $totalDpPaid + $totalSudahDibayar;
             $totalOverallProfit = Transaksi::sum('total_price');
             
             $packetsForFilter = Packet::with('product')->whereHas('product')->orderBy('name')->get()->groupBy('product.name');

            return view('admin.transaction.transaksi', compact(
                'transactions', 'perPageOptions', 'totalBelumDibayar', 'countBelumDibayar',
                'totalDpPaid', 'countDp', 'totalSudahDibayar', 'countSudahDibayar',
                'totalProfit', 'totalOverallProfit', 'packetsForFilter', 'paymentStatuses', 'processStatuses'
            ));
        }
    }

    public function create()
    {
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        return view('admin.transaction.create', compact('packets', 'all_additionals'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name'  => ['required', 'string', 'max:50'],
            'phone_number'   => ['required', 'string', 'max:20'],
            'status'         => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'packet_id'      => ['required', 'exists:packets,id'],
            'additionals'    => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'dp_amount'      => ['nullable', 'numeric', 'min:0', 'required_if:status,dp'],
            'note'           => ['nullable', 'string'],
            'url_images'     => ['nullable', 'string', 'url'],
            'select_edit_photo' => ['nullable', 'string'],
            'select_print_photo'=> ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::firstOrCreate(
                ['username' => $validatedData['phone_number']],
                [
                    'name' => $validatedData['customer_name'],
                    'password' => Hash::make($validatedData['phone_number']),
                    'role_id' => Role::where('name', 'User')->firstOrFail()->id,
                ]
            );

            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $details) {
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }

            $totalPrice = $subtotal - $discount;

            // Update Logika Status Awal
            $initialProcessStatus = !empty($validatedData['url_images']) ? 'Pelanggan Pilih Foto' : 'Pelanggan Belum Foto';

            $transaksi = Transaksi::create([
                'user_id'         => $user->id,
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'process_status'  => $initialProcessStatus,
                'packet_id'       => $validatedData['packet_id'],
                'receipt_code'    => 'TEMP-' . uniqid(),
                'total_price'     => max(0, $totalPrice),
                'dp_amount'       => $validatedData['status'] === 'dp' ? $validatedData['dp_amount'] : null,
                'discount'        => $discount,
                'note'            => $validatedData['note'],
                'url_images'      => $validatedData['url_images'] ?? null,
                'select_edit_photo'  => $validatedData['select_edit_photo'] ?? null,
                'select_print_photo' => $validatedData['select_print_photo'] ?? null,
            ]);

            if (!empty($validatedData['additionals'])) {
                $syncData = [];
                foreach ($validatedData['additionals'] as $id => $details) {
                    $syncData[$id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                }
                $transaksi->additionals()->sync($syncData);
            }

            $transaksi->receipt_code = "INV/" . Carbon::now()->format('Ymd') . "/" . $transaksi->transaction_id;
            $transaksi->save();

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $transaksi = Transaksi::with(['packet', 'additionals'])->findOrFail($id);
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        $canPrint = $transaksi->hasPrintableItems();
        return view('admin.transaction.edit', compact('transaksi', 'packets', 'all_additionals', 'canPrint'));
    }

    public function update(Request $request, string $id)
    {
        $transaksi = Transaksi::with('user')->findOrFail($id);

        $validatedData = $request->validate([
            'customer_name'  => ['required', 'string', 'max:50'],
            'phone_number'   => ['required', 'string', 'max:20', Rule::unique('users', 'username')->ignore($transaksi->user_id)],
            'status'         => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            // Update Validasi Rule Status
            'process_status' => ['required', Rule::in($this->processStatuses)],
            'packet_id'      => ['required', 'exists:packets,id'],
            'additionals'    => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'dp_amount'      => ['nullable', 'numeric', 'min:0', 'required_if:status,dp'],
            'note'           => ['nullable', 'string'],
            'url_images'     => ['nullable', 'string', 'url'],
            'select_edit_photo' => ['nullable', 'string'],
            'select_print_photo'=> ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            if ($transaksi->user) {
                $transaksi->user->update([
                    'name' => $validatedData['customer_name'],
                    'username' => $validatedData['phone_number'],
                ]);
            }

            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $details) {
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }

            $totalPrice = $subtotal - $discount;
            $previousStatus = $transaksi->status;
            
            // Update Logika Auto Status
            $processStatus = $validatedData['process_status'];
            if (!empty($validatedData['url_images']) && $processStatus === 'Pelanggan Belum Foto') {
                $processStatus = 'Pelanggan Pilih Foto';
            }

            $transaksi->update([
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'process_status'  => $processStatus,
                'packet_id'       => $validatedData['packet_id'],
                'total_price'     => max(0, $totalPrice),
                'dp_amount'       => $validatedData['status'] === 'dp' ? $validatedData['dp_amount'] : null,
                'discount'        => $discount,
                'note'            => $validatedData['note'],
                'url_images'      => $validatedData['url_images'] ?? null,
                'select_edit_photo'  => $validatedData['select_edit_photo'] ?? null,
                'select_print_photo' => $validatedData['select_print_photo'] ?? null,
            ]);
            
            if ($previousStatus !== 'sudah dibayar' && $validatedData['status'] === 'sudah dibayar') {
                $this->recordIncome($transaksi);
            }

            $syncData = [];
            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $additional_id => $details) {
                    $syncData[$additional_id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                }
            }
            $transaksi->additionals()->sync($syncData);

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        // Update logika pengecekan URL kosong
        if ($field === 'process_status' && $value !== 'Pelanggan Belum Foto' && empty($transaksi->url_images)) {
            return redirect()->back()->with('error', 'Cannot proceed. URL Photos must be filled first.');
        }

        $transaksi->{$field} = $value;
        if ($field === 'status') {
             $transaksi->dp_amount = ($value === 'dp') ? $request->input('dp_amount') : null;
             if ($value === 'sudah dibayar') {
                 $this->recordIncome($transaksi);
             }
        }
        
        $transaksi->save();
        
        if ($request->has('redirect_to') && $request->redirect_to == 'index') {
            return redirect()->route('transaksi.index')->with('success', 'Transaction status updated to ' . $value);
        }

        return back()->with('success', 'Status updated.');
    }

    public function destroy(string $id)
    {
        Transaksi::destroy($id);
        return back()->with('success', 'Deleted');
    }

    public function viewSelectForEdit(Transaksi $transaksi)
    {
        try {
            $transaksi->load([
                'packet.product', 
                'packet.printOptions', 
                'packet.additionalDefaults.additional',
                'additionals'
            ]);

            if (!$transaksi->packet) {
                return redirect()->back()->with('error', 'Transaction is not linked to a valid packet.');
            }
            
            return view('user.transaction.select-photo', [
                'transaksi' => $transaksi,
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * USER SIDE: Handle submission of photo selections.
     */
    public function handleSelectForEditUser(Request $request, Transaksi $transaksi)
    {
        $validatedData = $request->validate([
            'select_edit_photo'  => ['nullable', 'string'],
            'select_print_photo' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            // Update Logika Auto Status setelah User memilih
            $newStatus = ($transaksi->process_status === 'Pelanggan Pilih Foto') ? 'Siap Edit dan Cetak' : $transaksi->process_status;

            $transaksi->update([
                'select_edit_photo'  => $validatedData['select_edit_photo'] ?? null,
                'select_print_photo' => $validatedData['select_print_photo'] ?? null,
                'process_status'     => $newStatus
            ]);

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Pilihan foto berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan pilihan foto: ' . $e->getMessage());
        }
    }

    public function viewSelectionsForAdmin(Transaksi $transaksi, Request $request)
    {
        try {
            $selectedPhotos = $transaksi->select_edit_photo ? array_map('trim', explode(',', $transaksi->select_edit_photo)) : [];
            $selectedPrints = $transaksi->select_print_photo ? array_map('trim', explode(',', $transaksi->select_print_photo)) : [];

            return view('admin.transaction.view-selections', [
                'transaksi' => $transaksi,
                'selectedPhotos' => $selectedPhotos,
                'selectedPrints' => $selectedPrints,
                'urlImages' => $transaksi->url_images 
            ]);

        } catch (\Exception $e) {
            return redirect()->route('transaksi.index')->with('error', $e->getMessage());
        }
    }

    // ... (sisa method downloadInvoice, printInvoice, dll tetap sama) ...
    // Untuk mempersingkat, saya tidak menyertakan method yang tidak berubah.

    public function downloadInvoice(Transaksi $transaksi)
    {
        if ($transaksi->status != "sudah dibayar") {
            return back()->with('error', 'Only paid transactions can download invoices');
        }

        $transaksi->load(
            'packet.product',
            'packet.additionalDefaults.additional',
            'packet.printOptions',
            'additionals'
        );

        $data = [
            'transaksi' => $transaksi,
            'paymentStatusConfig' => [
                'belum dibayar' => 'Unpaid',
                'dp' => 'Down Payment',
                'sudah dibayar' => 'Paid'
            ],
            // Update config PDF (opsional, tapi baik untuk konsistensi)
            'processStatusConfig' => [
                'Pelanggan Belum Foto' => 'Not Photographed',
                'Pelanggan Pilih Foto' => 'Selecting Photos',
                'Siap Edit dan Cetak' => 'Ready to Edit/Print',
                'Proses Edit dan Cetak' => 'Processing',
                'Selesai' => 'Completed'
            ],
            'company' => [
                'name' => 'Yunas Studio',
                'address' => 'Jalan Lingkar Selatan, Sukabumi',
                'logo' => public_path('assets/images/yunas_dark.png')
            ]
        ];

        $pdf = Pdf::loadView('invoices.template', $data);
        $filename = "invoice_" . str_replace('/','-',$transaksi->receipt_code) . ".pdf";
        return $pdf->download($filename);
    }

    public function printInvoice(Transaksi $transaksi)
    {
        $transaksi->load(
            'packet.product',
            'packet.additionalDefaults.additional',
            'packet.printOptions', 
            'additionals'
        );

        return view('invoices.print-template', ['transaksi' => $transaksi]);
    }

    private function recordIncome(Transaksi $transaksi)
    {
        $description = "Billed To:\nName: {$transaksi->customer_name}\nPhone: {$transaksi->phone_number}\nInvoice Details:\nTransaction Date: " . $transaksi->created_at->format('d-m-Y');
        
        $categoryId = \App\Models\ExpenseCategory::where('name', 'Transaction')->first()->id ?? 1;
        
        \App\Models\Expense::create([
            'name' => $transaksi->receipt_code,
            'description' => $description,
            'amount' => $transaksi->total_price,
            'paid_amount' => $transaksi->total_price,
            'remaining_amount' => 0,
            'expense_date' => now(),
            'category_id' => $categoryId,
            'type' => 'income',
            'is_paid' => true,
        ]);
    }

    public function getDefaultAdditionals(Packet $packet)
    {
        $combinedDefaults = $packet->combined_defaults;
        return response()->json($combinedDefaults);
    }
}