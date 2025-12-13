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
use Illuminate\Support\Str;

class TransaksiController extends Controller
{
    // Definisi Urutan Status (Sangat Penting untuk Logika Berurutan)
    private $processStatuses = [
        'Pelanggan Belum Foto',
        'Pelanggan Pilih Foto',
        'Proses Edit',
        'Proses Cetak',
        'Selesai'
    ];

    // --- HELPER UTAMA UNTUK SECURITY (Mencegah IDOR) ---
    private function authorizeAccess(Transaksi $transaksi)
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isKasir()) {
            return true;
        }
        if ($transaksi->user_id !== $user->id) {
            abort(403, 'AKSES DITOLAK: Anda tidak memiliki izin untuk melihat transaksi ini.');
        }
    }

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
        // Eager load relationships termasuk detail paket dan additional
        $query = Transaksi::with([
            'packet.product', 
            'packet.printOptions', 
            'packet.additionalDefaults.additional', 
            'user', 
            'additionals'
        ]);
        
        $user = auth()->user();
        
        // Filter user biasa
        if ($user->isUser()) {
            $query->where('phone_number', $user->username);
        }
        
        // Filter Search
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

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortBy, ['total_price', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPageOptions = [5, 10, 20, 50, 100];
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 10;
        }
        $transactions = $query->paginate($perPage)->withQueryString();
        
        $processStatuses = $this->processStatuses;
        $paymentStatuses = ['belum dibayar', 'dp', 'sudah dibayar'];

        // Return View
        if ($user->isUser()) {
            $userPacketIds = (clone $financialQuery)->distinct()->pluck('packet_id');
            $packetsForFilter = Packet::with('product')->whereIn('id', $userPacketIds)->orderBy('name')->get()->groupBy('product.name');
            
            return view('user.transaction.transaksi', compact(
                'transactions', 'packetsForFilter', 'paymentStatuses', 'processStatuses', 'perPageOptions'
            ));
        } else {
             // Admin Stats
             $unpaidFull = (clone $financialQuery)->where('status', 'belum dibayar')->sum('total_price');
             $dpRemaining = (clone $financialQuery)->where('status', 'dp')->sum(DB::raw('total_price - COALESCE(dp_amount, 0)'));
             
             $totalBelumDibayar = $unpaidFull + $dpRemaining;
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
        $this->authorizeAdmin();
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        return view('admin.transaction.create', compact('packets', 'all_additionals'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

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

            // LOGIKA PEMASUKAN: Jika Lunas -> Catat; Jika Tidak -> Hapus (jika ada)
            if ($validatedData['status'] === 'sudah dibayar') {
                $this->recordIncome($transaksi);
            } else {
                $this->deleteIncome($transaksi);
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
        $this->authorizeAdmin();
        $transaksi = Transaksi::with(['packet', 'additionals'])->findOrFail($id);
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        $canPrint = $transaksi->hasPrintableItems();
        return view('admin.transaction.edit', compact('transaksi', 'packets', 'all_additionals', 'canPrint'));
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAdmin();
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
            
            // LOGIKA PEMASUKAN DI UPDATE
            if ($validatedData['status'] === 'sudah dibayar') {
                $this->recordIncome($transaksi);
            } else {
                // Jika status berubah dari 'sudah dibayar' ke 'belum dibayar'/'dp', hapus entry di expense
                $this->deleteIncome($transaksi);
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
        $this->authorizeAdmin();

        $transaksi = Transaksi::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        // Validasi Logika Bisnis
        if ($field === 'process_status') {
            if ($value !== 'Pelanggan Belum Foto' && empty($transaksi->url_images)) {
                return redirect()->back()->with('error', 'Gagal: Link pemilihan foto (URL Gallery) harus diisi terlebih dahulu.');
            }
            if ($value === 'Proses Cetak' && !$transaksi->hasPrintableItems()) {
                return redirect()->back()->with('error', 'Gagal: Transaksi ini tidak memiliki item untuk dicetak.');
            }
            if ($value === 'Selesai') {
                if ($transaksi->status !== 'sudah dibayar') {
                    return redirect()->back()->with('error', 'Gagal: Status pembayaran harus "Sudah Dibayar" sebelum menyelesaikan transaksi.');
                }
                if (empty($transaksi->url_photos_result)) {
                    return redirect()->back()->with('error', 'Gagal: Link hasil foto (Final Link) harus diisi sebelum menyelesaikan transaksi.');
                }
            }
        }

        // Handle update khusus field URL
        if (in_array($field, ['url_images', 'url_photos_result'])) {
            $transaksi->{$field} = $value;
            if ($field === 'url_images' && !empty($value) && $transaksi->process_status === 'Pelanggan Belum Foto') {
                $transaksi->process_status = 'Pelanggan Pilih Foto';
            }
            $transaksi->save();
            return redirect()->back()->with('success', 'Link berhasil diperbarui.');
        }

        // Handle update status biasa
        $transaksi->{$field} = $value;
        if ($field === 'status') {
             $transaksi->dp_amount = ($value === 'dp') ? $request->input('dp_amount') : null;
             
             // LOGIKA PEMASUKAN DI UPDATE STATUS (AJAX/DIRECT)
             if ($value === 'sudah dibayar') {
                 $this->recordIncome($transaksi);
             } else {
                 $this->deleteIncome($transaksi);
             }
        }
        
        $transaksi->save();
        
        if ($request->has('redirect_to') && $request->redirect_to == 'index') {
            return redirect()->route('transaksi.index')->with('success', 'Transaction status updated to ' . $value);
        }

        return back()->with('success', 'Status updated.');
    }

    public function updateSelections(Request $request, Transaksi $transaksi)
    {
        $this->authorizeAdmin();

        $request->validate([
            'selection_text' => 'nullable|string',
        ]);

        $text = $request->input('selection_text');
        $editPhotos = null;
        $printPhotos = null;

        if ($text) {
            $editHeaderPos = stripos($text, 'DAFTAR FOTO EDIT');
            $printHeaderPos = stripos($text, 'DAFTAR FOTO CETAK');

            if ($editHeaderPos !== false) {
                $start = $editHeaderPos + strlen('DAFTAR FOTO EDIT');
                if (preg_match('/\(.*?\)/', substr($text, $start), $matches, PREG_OFFSET_CAPTURE)) {
                     $start += $matches[0][1] + strlen($matches[0][0]);
                }
                $length = ($printHeaderPos !== false) ? $printHeaderPos - $start : strlen($text);
                $editPhotos = trim(substr($text, $start, $length));
            }

            if ($printHeaderPos !== false) {
                $start = $printHeaderPos + strlen('DAFTAR FOTO CETAK');
                $endPos = stripos($text, 'Terima kasih', $start);
                $length = ($endPos !== false) ? $endPos - $start : strlen($text);
                $printPhotos = trim(substr($text, $start, $length));
            }

            if (!$editPhotos && !$printPhotos && !$editHeaderPos && !$printHeaderPos) {
                $editPhotos = $text; 
            }
        }

        $transaksi->select_edit_photo = $editPhotos;
        $transaksi->select_print_photo = $printPhotos;
        
        if ($transaksi->process_status === 'Pelanggan Pilih Foto' && (!empty($editPhotos) || !empty($printPhotos))) {
            $transaksi->process_status = 'Proses Edit';
        }

        $transaksi->save();

        return redirect()->back()->with('success', 'Data pilihan foto pelanggan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $this->authorizeAdmin();
        $transaksi = Transaksi::findOrFail($id);
        
        // Hapus juga data pemasukan terkait sebelum menghapus transaksi
        $this->deleteIncome($transaksi);
        
        $transaksi->delete();
        return back()->with('success', 'Deleted');
    }

    // ... [Other methods unchanged] ...
    
    public function viewSelectForEdit(Transaksi $transaksi)
    {
        try {
            $transaksi->load(['packet.product', 'packet.printOptions', 'packet.additionalDefaults.additional', 'additionals']);
            return view('user.transaction.select-photo', ['transaksi' => $transaksi]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    public function handleSelectForEditUser(Request $request, Transaksi $transaksi) { /* ... */ }
    public function viewSelectionsForAdmin(Transaksi $transaksi, Request $request) { /* ... */ }

    public function downloadInvoice(Transaksi $transaksi)
    {
        $this->authorizeAccess($transaksi);
        if ($transaksi->status != "sudah dibayar") {
            return back()->with('error', 'Only paid transactions can download invoices');
        }
        $transaksi->load('packet.product', 'packet.additionalDefaults.additional', 'packet.printOptions', 'additionals');
        $data = [
            'transaksi' => $transaksi,
            'paymentStatusConfig' => ['belum dibayar' => 'Unpaid', 'dp' => 'Down Payment', 'sudah dibayar' => 'Paid'],
            'processStatusConfig' => ['Pelanggan Belum Foto' => 'Not Photographed', 'Pelanggan Pilih Foto' => 'Selecting Photos', 'Proses Edit' => 'Editing', 'Proses Cetak' => 'Printing', 'Selesai' => 'Completed'],
            'company' => ['name' => 'Yunas Studio', 'address' => 'Jalan Lingkar Selatan, Sukabumi', 'logo' => public_path('assets/images/yunas_dark.png')]
        ];
        $pdf = Pdf::loadView('invoices.template', $data);
        $filename = "invoice_" . str_replace('/','-',$transaksi->receipt_code) . ".pdf";
        return $pdf->download($filename);
    }

    public function printInvoice(Transaksi $transaksi)
    {
        $this->authorizeAdmin();
        $transaksi->load('packet.product', 'packet.additionalDefaults.additional', 'packet.printOptions', 'additionals');
        return view('invoices.print-template', ['transaksi' => $transaksi]);
    }

    // Placeholder methods
    public function downloadAllPhotosAsZip(Transaksi $transaksi) { return back()->with('error', 'Fitur belum diimplementasikan.'); }
    public function downloadSelectedPhotosAsZip(Transaksi $transaksi) { return back()->with('error', 'Fitur belum diimplementasikan.'); }
    public function downloadFolderAsZip(Transaksi $transaksi, $status) { return back()->with('error', 'Fitur belum diimplementasikan.'); }
    public function viewResultPhotos(Transaksi $transaksi) { $this->authorizeAccess($transaksi); return view('user.transaction.result-photo', compact('transaksi')); }
    public function viewSelectForPrint(Transaksi $transaksi) { $this->authorizeAccess($transaksi); return view('user.transaction.manage-print-photo', compact('transaksi')); }
    public function handleSelectForPrint(Request $request, Transaksi $transaksi) { $this->authorizeAccess($transaksi); return redirect()->back()->with('success', 'Saved.'); }

    // --- Helper untuk Pemasukan ---

    private function recordIncome(Transaksi $transaksi)
    {
        $description = "Billed To:\nName: {$transaksi->customer_name}\nPhone: {$transaksi->phone_number}\nInvoice Details:\nTransaction Date: " . $transaksi->created_at->format('d-m-Y');
        $categoryId = ExpenseCategory::where('name', 'Transaction')->first()->id ?? 1;
        
        // Gunakan updateOrCreate agar tidak duplikat jika dipanggil berulang kali untuk transaksi yang sama
        Expense::updateOrCreate(
            [
                'name' => $transaksi->receipt_code, 
                'type' => 'income'
            ],
            [
                'description' => $description,
                'amount' => $transaksi->total_price,
                'paid_amount' => $transaksi->total_price,
                'remaining_amount' => 0,
                'expense_date' => now(), // Tanggal pemasukan diupdate ke waktu pembayaran lunas
                'category_id' => $categoryId,
                'is_paid' => true,
            ]
        );
    }

    private function deleteIncome(Transaksi $transaksi)
    {
        // Cari dan hapus expense yang terhubung dengan kode resi transaksi ini
        Expense::where('name', $transaksi->receipt_code)
               ->where('type', 'income')
               ->delete();
    }

    public function getDefaultAdditionals(Packet $packet)
    {
        return response()->json($packet->combined_defaults);
    }
}