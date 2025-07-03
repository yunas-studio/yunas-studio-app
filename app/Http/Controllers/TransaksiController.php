<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\Packet;
use App\Models\SelectedPhoto;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request('search');
        if (auth()->user()->isUser()) {
            $transactions = Transaksi::with([
                'packet.product',
                'packet.additionalDefaults.additional', // Included items
                'additionals' // Extra items
            ])
                ->when($search, function ($query) use ($search) {
                    return $query->where('receipt_code', 'like', "%$search%")
                        ->orWhere('customer_name', 'like', "%$search%");
                })
                ->where('phone_number', auth()->user()->username)
                ->orderByDesc('created_at')
                ->paginate(10);
            return view('user.transaction.transaksi', compact('transactions'));
        }
        $belumDibayarCount = Transaksi::where('status', 'belum dibayar')->count();
        $dpCount = Transaksi::where('status', 'dp')->count();
        $sudahDibayarCount = Transaksi::where('status', 'sudah dibayar')->count();

        $transactions = Transaksi::with([
            'packet.product',
            'packet.additionalDefaults.additional',
            'additionals',
            'user'
        ])
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
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
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
            'phone_number'    => ['required', 'string', 'max:20'],
            'status'          => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'packet_id'       => ['required', 'exists:packets,id'],
            'additionals'     => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string'],
            'temporary_link'  => ['nullable', 'url', 'max:255'],
            'selected_photos' => ['nullable', 'url', 'max:255'],
            'final_link'      => ['nullable', 'url', 'max:255'],
        ]);

        DB::beginTransaction();
        try {
            // Find or Create User Logic
            $user = User::where('username', $validatedData['phone_number'])->first();

            if (!$user) {
                $userRole = Role::where('name', 'User')->firstOrFail();
                $user = User::create([
                    'name' => $validatedData['customer_name'],
                    'username' => $validatedData['phone_number'],
                    'password' => Hash::make($validatedData['phone_number']),
                    'role_id' => $userRole->id,
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

            $transaksi = Transaksi::create([
                'user_id'         => $user->id,
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'packet_id'       => $validatedData['packet_id'],
                'receipt_code'    => 'TEMP-' . uniqid(),
                'total_price'     => max(0, $totalPrice),
                'discount'        => $discount,
                'note'            => $validatedData['note'],
                'temporary_link'  => $validatedData['temporary_link'],
                'selected_photos' => $validatedData['selected_photos'],
                'final_link'      => $validatedData['final_link'],
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

            try {
                // 1. Define the path for the main 'photos' directory
                $basePhotosPath = storage_path('app/public/photos');

                if (!File::isDirectory($basePhotosPath)) {
                    File::makeDirectory($basePhotosPath, 0755, true, true);
                }

                $folderName = str_replace('/', '_', $transaksi->receipt_code);
                $folderPath = $basePhotosPath . '/' . $folderName;
                $folderPath = $basePhotosPath . '/' . $folderName;

                if (!File::isDirectory($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true, true);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create photo directory for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
            }

            return redirect()->route('transaksi.index')->with('success', 'Transaction created successfully. Customer account linked.');
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
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::orderBy('name')->get();
        return view('admin.transaction.edit', compact('transaksi', 'packets', 'all_additionals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transaksi = Transaksi::with('user')->findOrFail($id);

        $validatedData = $request->validate([
            'customer_name'   => ['required', 'string', 'max:50'],
            // Add validation to ensure the new phone number isn't already taken by another user
            'phone_number'    => ['required', 'string', 'max:20', Rule::unique('users', 'username')->ignore($transaksi->user_id)],
            'status'          => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'process_status'  => ['required', Rule::in(['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])],
            'packet_id'       => ['required', 'exists:packets,id'],
            'additionals'     => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string'],
            'temporary_link'  => ['nullable', 'url', 'max:255'],
            'selected_photos' => ['nullable', 'url', 'max:255'],
            'final_link'      => ['nullable', 'url', 'max:255'],
        ]);

        DB::beginTransaction();
        try {
            // --- Update Existing User Logic ---
            if ($transaksi->user) {
                $transaksi->user->update([
                    'name' => $validatedData['customer_name'],
                    'username' => $validatedData['phone_number'],
                ]);
            }
            // --- End User Logic ---

            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $details) {
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }

            $totalPrice = $subtotal - $discount;

            $transaksi->update([
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'process_status'  => $validatedData['process_status'],
                'packet_id'       => $validatedData['packet_id'],
                'total_price'     => max(0, $totalPrice),
                'discount'        => $discount,
                'note'            => $validatedData['note'],
                'temporary_link'  => $validatedData['temporary_link'],
                'selected_photos' => $validatedData['selected_photos'],
                'final_link'      => $validatedData['final_link'],
            ]);

            $syncData = [];
            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $additional_id => $details) {
                    $syncData[$additional_id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                }
            }
            $transaksi->additionals()->sync($syncData);

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction and customer details updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction.');
        }
    }

    /**
     * Get the default additionals for a given packet via AJAX.
     */
    public function getDefaultAdditionals(Packet $packet)
    {
        $defaults = $packet->additionalDefaults()->with('additional')->get();
        return response()->json($defaults);
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
        } elseif ($field === 'process_status' && !in_array($value, ['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])) {
            return redirect()->back()->with('error', 'Invalid process status value.');
        }

        $transaksi->{$field} = $value;
        $transaksi->save();

        return redirect()->back()->with('success', 'Status updated successfully.');
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

    public function viewSelectPhotos(Transaksi $transaksi)
    {
        try {
            // Get photo directory information
            $photoData = $this->getPhotoDirectoryData($transaksi, 'raw');

            // Get selected photos
            $selectedPhotos = SelectedPhoto::where('transaction_id', $transaksi->transaction_id)
                ->pluck('file_url')
                ->toArray();
            return view('user.transaction.manage-photo', [
                'transaksi' => $transaksi,
                'photoUrls' => $photoData['urls'],
                'folderName' => $photoData['folderName'],
                'totalImage' => config('app.max_selected_photos', 5),
                'selectedPhotos' => $selectedPhotos,
                'photoCount' => count($photoData['urls']),
            ]);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    protected function getPhotoDirectoryData($transaksi, $status)
    {
        $folderName = str_replace('/','_',$transaksi->receipt_code);
        $relativePath = "photos/{$folderName}/{$status}";
        $fullPath = storage_path("app/public/{$relativePath}");

        if (!file_exists($fullPath)) {
            throw new \Exception("No photos available for this transaction");
        }

        $files = scandir($fullPath);
        $photoFiles = array_filter($files, function($file) {
            return $this->isValidImageFile($file);
        });

        if (empty($photoFiles)) {
            throw new \Exception("No valid photos found in directory");
        }

        $photoFiles = array_values($photoFiles);

        $photoUrls = array_map(function($file) use ($relativePath) {
            return asset("storage/{$relativePath}/{$file}");
        }, $photoFiles);

        return [
            'folderName' => $folderName,
            'urls' => $photoUrls,
            'count' => count($photoUrls)
        ];
    }

    protected function isValidImageFile($file)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return !in_array($file, ['.', '..']) &&
            in_array($extension, $allowedExtensions);
    }

    public function downloadInvoice(Transaksi $transaksi)
    {
        if ($transaksi->status != "sudah dibayar") {
            return back()->with('error', 'Only paid transactions can download invoices');
        }

        $data = [
            'transaksi' => $transaksi,
            'paymentStatusConfig' => [
                'belum dibayar' => 'Unpaid',
                'dp' => 'Down Payment',
                'sudah dibayar' => 'Paid'
            ],
            'processStatusConfig' => [
                'Belum Foto' => 'Not Photographed',
                'Pilih Foto' => 'Selecting Photos',
                'Siap Edit' => 'Ready to Edit',
                'Proses Edit' => 'Editing in Progress',
                'Selesai Editing' => 'Editing Completed',
                'Siap Cetak' => 'Ready to Print',
                'Proses Cetak' => 'Printing',
                'Selesai' => 'Completed'
            ],
            'company' => [
                'name' => 'Your Company Name',
                'address' => 'Jalan Lingkar Selatan, Sukabumi',
                'logo' => public_path('assets/images/yunas_dark.png')
            ]
        ];

        $pdf = Pdf::loadView('invoices.template', $data);

        $filename = "invoice_" . str_replace('/','-',$transaksi->receipt_code) . ".pdf";

        return $pdf->download($filename);
    }

    public function selectImage(Request $request, $transaction)
    {
        try {
            $request->validate([
                'photo_urls' => 'required|array',
                'photo_urls.*' => 'required|string|max:255',
            ]);

            // Get the actual process_status value, not the entire object
            $existingTransaction = Transaksi::findOrFail($transaction);
            $currentStatus = $existingTransaction->process_status;
            
            if(!in_array($currentStatus, ['Pilih Foto', 'Siap Edit'])) {
                throw new \Exception("Status transaksi tidak valid untuk pemilihan foto");
            }

            $existingPhotos = SelectedPhoto::where('transaction_id', $transaction)
                ->get(['id', 'file_url'])
                ->pluck('file_url', 'id')
                ->toArray();

            $newPhotos = $request->photo_urls;

            $photosToDelete = array_diff($existingPhotos, $newPhotos);
            
            $photosToAdd = array_diff($newPhotos, $existingPhotos);

            DB::transaction(function () use ($transaction, $photosToDelete, $photosToAdd, $newPhotos) {
                if (!empty($photosToDelete)) {
                    $deleteIds = array_keys(array_intersect($existingPhotos, $photosToDelete));
                    SelectedPhoto::whereIn('id', $deleteIds)->delete();
                }

                foreach ($photosToAdd as $url) {
                    SelectedPhoto::create([
                        'transaction_id' => $transaction,
                        'file_url' => $url
                    ]);
                }

                Transaksi::where('transaction_id', $transaction)->update([
                    'process_status' => 'Siap Edit',
                    'updated_at' => now() // Explicit timestamp update
                ]);
            });

            return redirect()
                ->route('transaksi.view-select-photos', $transaction)
                ->with('success', 'Foto yang dipilih berhasil diperbarui');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function viewResultPhotos(Transaksi $transaksi)
    {
        try {
            // Get photo directory information
            $photoData = $this->getPhotoDirectoryData($transaksi, 'result');

            return view('user.transaction.result-photo', [
                'transaksi'=>$transaksi,
                'photoUrls' => $photoData['urls'],
                'photoCount' => count($photoData['urls']),
            ]);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
