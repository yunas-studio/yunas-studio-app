<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\Packet;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Role;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TransaksiController extends Controller
{
    // Definisi Status
    private $processStatuses = [
        'Pelanggan Belum Foto',
        'Pelanggan Pilih Foto',
        'Siap Edit dan Cetak',
        'Proses Edit dan Cetak',
        'Selesai'
    ];

    // --- HELPER UTAMA UNTUK SECURITY (Mencegah IDOR) ---
    
    /**
     * Memastikan hanya pemilik data atau Admin/Kasir yang bisa akses.
     */
    private function authorizeAccess(Transaksi $transaksi)
    {
        $user = auth()->user();
        
        // 1. Jika Admin atau Kasir, izinkan akses
        if ($user->isAdmin() || $user->isKasir()) {
            return true;
        }

        // 2. Jika User biasa, cek apakah ID-nya cocok dengan pemilik transaksi
        if ($transaksi->user_id !== $user->id) {
            abort(403, 'AKSES DITOLAK: Anda tidak memiliki izin untuk melihat transaksi ini.');
        }
    }

    /**
     * Memastikan HANYA Admin atau Kasir yang bisa akses (User biasa ditolak).
     */
    private function authorizeAdmin()
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isKasir()) {
            abort(403, 'AKSES DITOLAK: Halaman ini khusus Admin/Kasir.');
        }
    }

    // ----------------------------------------------------

    public function index(Request $request)
    {
        $query = Transaksi::with(['packet.product', 'packet.printOptions', 'user', 'additionals']);
        $user = auth()->user();
        
        // Filter otomatis: User hanya melihat datanya sendiri
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
        $this->authorizeAdmin(); // Security Check: Hanya Admin
        
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        return view('admin.transaction.create', compact('packets', 'all_additionals'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin(); // Security Check: Hanya Admin

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
            'url_photos_result' => ['nullable', 'string', 'url'],
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
                    'role_id' => Role::where('name', 'User')->first()->id ?? 3,
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
                'url_photos_result' => $validatedData['url_photos_result'] ?? null,
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

            if ($validatedData['status'] === 'sudah dibayar') {
                $this->recordIncome($transaksi);
            }

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $this->authorizeAdmin(); // Security Check: Hanya Admin

        $transaksi = Transaksi::with(['packet', 'additionals'])->findOrFail($id);
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        $canPrint = $transaksi->hasPrintableItems();
        return view('admin.transaction.edit', compact('transaksi', 'packets', 'all_additionals', 'canPrint'));
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAdmin(); // Security Check: Hanya Admin

        $transaksi = Transaksi::with('user')->findOrFail($id);

        $validatedData = $request->validate([
            'customer_name'  => ['required', 'string', 'max:50'],
            'phone_number'   => ['required', 'string', 'max:20'],
            'status'         => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'process_status' => ['required', Rule::in($this->processStatuses)],
            'packet_id'      => ['required', 'exists:packets,id'],
            'additionals'    => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'dp_amount'      => ['nullable', 'numeric', 'min:0', 'required_if:status,dp'],
            'note'           => ['nullable', 'string'],
            'url_images'     => ['nullable', 'string', 'url'],
            'url_photos_result' => ['nullable', 'string', 'url'],
            'select_edit_photo' => ['nullable', 'string'],
            'select_print_photo'=> ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            // FIX BUG 2: Ganti User jika nomor HP berubah
            $user = User::firstOrCreate(
                ['username' => $validatedData['phone_number']],
                [
                    'name' => $validatedData['customer_name'],
                    'password' => Hash::make($validatedData['phone_number']),
                    'role_id' => Role::where('name', 'User')->first()->id ?? 3,
                ]
            );

            if ($user->name !== $validatedData['customer_name']) {
                $user->update(['name' => $validatedData['customer_name']]);
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
            
            $processStatus = $validatedData['process_status'];
            if (!empty($validatedData['url_images']) && $processStatus === 'Pelanggan Belum Foto') {
                $processStatus = 'Pelanggan Pilih Foto';
            }

            $transaksi->update([
                'user_id'         => $user->id,
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
                'url_photos_result' => $validatedData['url_photos_result'] ?? null,
                'select_edit_photo'  => $validatedData['select_edit_photo'] ?? null,
                'select_print_photo' => $validatedData['select_print_photo'] ?? null,
            ]);
            
            // FIX BUG 1: Panggil fungsi yang sudah aman dari duplikasi
            if ($validatedData['status'] === 'sudah dibayar') {
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
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorizeAdmin(); // Security Check: Hanya Admin

        $transaksi = Transaksi::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

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
        $this->authorizeAdmin(); // Security Check: Hanya Admin
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
                'additionals' // Load extra items purchased
            ]);

            if (!$transaksi->packet) {
                return redirect()->back()->with('error', 'Transaction is not linked to a valid packet.');
            }

            // --- 1. Generate Template for Editing ---
            // If user already saved data, use that. Otherwise, generate the template.
            if ($transaksi->select_edit_photo) {
                $editValue = $transaksi->select_edit_photo;
            } else {
                $maxEdit = $transaksi->packet->max_photos_for_edit;
                $editValue = "Daftar Foto untuk Diedit (Maks {$maxEdit} Foto):\n";
                for ($i = 1; $i <= $maxEdit; $i++) {
                    $editValue .= "{$i}. \n";
                }
            }

            // --- 2. Generate Template for Printing ---
            if ($transaksi->select_print_photo) {
                $printValue = $transaksi->select_print_photo;
            } else {
                $printValue = "Daftar Foto untuk Dicetak:\n";
                
                // A. From Packet Defaults (Combined accessor from Packet model)
                foreach ($transaksi->packet->combined_defaults as $item) {
                    // Check if item name contains "Cetak" or "Print"
                    if (stripos($item->name, 'cetak') !== false || stripos($item->name, 'print') !== false) {
                        for ($q = 0; $q < $item->quantity; $q++) {
                            $printValue .= "- {$item->name} : \n";
                        }
                    }
                }

                // B. From Additional Items (Extras bought by user)
                foreach ($transaksi->additionals as $additional) {
                    if (stripos($additional->name, 'cetak') !== false || stripos($additional->name, 'print') !== false) {
                        for ($q = 0; $q < $additional->pivot->quantity; $q++) {
                            $printValue .= "- (Add-on) {$additional->name} : \n";
                        }
                    }
                }
            }
            
            return view('user.transaction.select-photo', [
                'transaksi' => $transaksi,
                'editValue' => $editValue,
                'printValue' => $printValue
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function handleSelectForEditUser(Request $request, Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi); // Security Check: Owner atau Admin

        $validatedData = $request->validate([
            'select_edit_photo'  => ['nullable', 'string'],
            'select_print_photo' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
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
        $this->authorizeAdmin(); // Security Check: Hanya Admin

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

    public function downloadInvoice(Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi); // Security Check: Owner atau Admin

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
        $this->authorizeAdmin(); // Security Check: Hanya Admin

        $transaksi->load(
            'packet.product',
            'packet.additionalDefaults.additional',
            'packet.printOptions', 
            'additionals'
        );

        return view('invoices.print-template', ['transaksi' => $transaksi]);
    }

    // Placeholder methods for future implementation, secured
    public function downloadAllPhotosAsZip(Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi);
        return back()->with('error', 'Fitur Download All belum diimplementasikan.');
    }

    public function downloadSelectedPhotosAsZip(Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi);
        return back()->with('error', 'Fitur Download Selected belum diimplementasikan.');
    }

    public function downloadFolderAsZip(Transaksi $transaksi, $status)
    {
        $this->authorizeAccess($transaksi);
        return back()->with('error', 'Fitur Download Folder belum diimplementasikan.');
    }
    
    // Method route tambahan jika ada di web.php
    public function viewResultPhotos(Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi);
        // Logika view result photo disini...
        return view('user.transaction.result-photo', compact('transaksi'));
    }
    
    public function viewSelectForPrint(Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi);
        // Logika view select for print...
        return view('user.transaction.manage-print-photo', compact('transaksi'));
    }

    public function handleSelectForPrint(Request $request, Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi);
        // Logika save select for print...
        return redirect()->back()->with('success', 'Print selection saved.');
    }

    private function recordIncome(Transaksi $transaksi)
    {
        // FIX BUG 1: Cek Duplikasi
        $exists = Expense::where('name', $transaksi->receipt_code)
            ->where('type', 'income')
            ->exists();

        if ($exists) {
            return;
        }

        $description = "Billed To:\nName: {$transaksi->customer_name}\nPhone: {$transaksi->phone_number}\nInvoice Details:\nTransaction Date: " . $transaksi->created_at->format('d-m-Y');
        
        $categoryId = ExpenseCategory::where('name', 'Transaction')->first()->id ?? 1;
        
        Expense::create([
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