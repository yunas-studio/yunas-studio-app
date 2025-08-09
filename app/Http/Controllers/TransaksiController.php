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
    // In app/Http/Controllers/TransaksiController.php

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

            $transaksi = Transaksi::create([
                'user_id'         => $user->id,
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'packet_id'       => $validatedData['packet_id'],
                'receipt_code'    => 'TEMP-' . uniqid(),
                'total_price'     => max(0, $totalPrice),
                'dp_amount'       => $validatedData['status'] === 'dp' ? $validatedData['dp_amount'] : null,
                'discount'        => $discount,
                'note'            => $validatedData['note'],
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

            // Folder Creation Logic
            try {
                $folderName = str_replace('/', '_', $transaksi->receipt_code);
                $baseTransactionPath = storage_path('app/public/photos/' . $folderName);
                $subfolders = ['RAW', 'Pilih Edit', 'Result', 'Pilih Cetak'];

                foreach ($subfolders as $subfolder) {
                    $path = $baseTransactionPath . '/' . $subfolder;
                    if (!File::isDirectory($path)) {
                        File::makeDirectory($path, 0755, true, true);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to create photo directories for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
            }

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
            'customer_name'  => ['required', 'string', 'max:50'],
            'phone_number'   => ['required', 'string', 'max:20', Rule::unique('users', 'username')->ignore($transaksi->user_id)],
            'status'         => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'process_status' => ['required', Rule::in(['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])],
            'packet_id'      => ['required', 'exists:packets,id'],
            'additionals'    => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'dp_amount'      => ['nullable', 'numeric', 'min:0', 'required_if:status,dp'],
            'note'           => ['nullable', 'string'],
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

            $transaksi->update([
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'process_status'  => $validatedData['process_status'],
                'packet_id'       => $validatedData['packet_id'],
                'total_price'     => max(0, $totalPrice),
                'dp_amount'       => $validatedData['status'] === 'dp' ? $validatedData['dp_amount'] : null,
                'discount'        => $discount,
                'note'            => $validatedData['note'],
            ]);

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
            Log::error('Transaction update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction.');
        }
    }

    /**
     * Get the default additionals for a given packet via AJAX.
     */
    public function getDefaultAdditionals(Packet $packet)
    {
        // Now it uses our new accessor to get both regular and print defaults
        $combinedDefaults = $packet->combined_defaults;
        return response()->json($combinedDefaults);
    }

    /**
     * Handle inline status updates from the index page.
     */
    // In app/Http/Controllers/TransaksiController.php

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['status', 'process_status'])],
            'value' => ['required', 'string'],
            'dp_amount' => ['nullable', 'numeric', 'min:0', 'required_if:value,dp'],
        ]);

        $transaksi = Transaksi::findOrFail($id);
        $field = $validated['field'];
        $value = $validated['value'];

        // Extra validation for status values
        if ($field === 'status' && !in_array($value, ['belum dibayar', 'dp', 'sudah dibayar'])) {
            return redirect()->back()->with('error', 'Invalid payment status value.');
        } elseif ($field === 'process_status' && !in_array($value, ['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])) {
            return redirect()->back()->with('error', 'Invalid process status value.');
        }

        try {
            $transaksi->{$field} = $value;

            // If payment status is updated, handle the dp_amount
            if ($field === 'status') {
                if ($value === 'dp') {
                    $transaksi->dp_amount = $validated['dp_amount'];
                } else {
                    $transaksi->dp_amount = null;
                }
            }

            // --- NEW LOGIC: Clean up data when reverting process status ---
            if ($field === 'process_status') {
                // If reverting back to the photo selection step, reset selections.
                if ($value === 'Pilih Foto') {
                    // 1. Delete previous edit selections from the database
                    $transaksi->selectedPhotos()->delete();

                    // 2. Clear the 'Pilih Edit' folder of old symlinks
                    $folderName = str_replace('/', '_', $transaksi->receipt_code);
                    $pilihEditPath = storage_path("app/public/photos/{$folderName}/Pilih Edit");

                    if (File::isDirectory($pilihEditPath)) {
                        File::cleanDirectory($pilihEditPath);
                    }
                }
            }
            // --- END OF NEW LOGIC ---

            $transaksi->save();

            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Failed to update status.');
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

    // In app/Http/Controllers/TransaksiController.php

    // This method is now for selecting photos TO BE EDITED
    public function viewSelectForEdit(Transaksi $transaksi)
    {
        try {
            $photoData = $this->getPhotoDirectoryData($transaksi, 'RAW');
            $selectedPhotos = SelectedPhoto::where('transaction_id', $transaksi->transaction_id)->pluck('file_url')->toArray();

            $photoLimit = $transaksi->packet->max_photos_for_edit ?? 10;

            return view('user.transaction.manage-photo', [
                'transaksi' => $transaksi,
                'photoUrls' => $photoData['urls'],
                'pageTitle' => 'Select Photos for Editing',
                'formAction' => route('transaksi.handle-select-for-edit', $transaksi),
                'selectedPhotos' => $selectedPhotos,
                'photoCount' => $photoData['count'],
                'totalImage' => $photoLimit, // Use the dynamic value here
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    // This method handles the submission from the "Select for Edit" page

    // In TransaksiController.php
    public function handleSelectForEdit(Request $request, Transaksi $transaksi)
    {
        if (!in_array($transaksi->process_status, ['Pilih Foto', 'Siap Edit'])) {
            return redirect()->back()->with('error', 'Photo selection is locked because the editing process has already begun.');
        }

        $request->validate(['photo_urls' => 'sometimes|array', 'photo_urls.*' => 'string']);
        $selectedUrls = $request->input('photo_urls', []);

        DB::transaction(function () use ($transaksi, $selectedUrls) {
            $transaksi->selectedPhotos()->delete();
            if (!empty($selectedUrls)) {
                $dataToInsert = collect($selectedUrls)->map(function ($url) use ($transaksi) {
                    return ['transaction_id' => $transaksi->transaction_id, 'file_url' => $url, 'created_at' => now(), 'updated_at' => now()];
                })->all();
                SelectedPhoto::insert($dataToInsert);
            }

            $folderName = str_replace('/', '_', $transaksi->receipt_code);
            $pilihEditPath = storage_path("app/public/photos/{$folderName}/Pilih Edit");
            File::cleanDirectory($pilihEditPath);

            foreach ($selectedUrls as $url) {
                $rawFileName = basename($url);
                $sourcePath = storage_path("app/public/photos/{$folderName}/RAW/{$rawFileName}");
                $linkPath = "{$pilihEditPath}/{$rawFileName}";
                if (File::exists($sourcePath) && !File::exists($linkPath)) {
                    File::link($sourcePath, $linkPath);
                }
            }

            if ($transaksi->process_status === 'Pilih Foto') {
                $transaksi->update(['process_status' => 'Siap Edit']);
            }
        });

        // Data for the success pop-up
        $redirectData = [
            'success_title'   => 'Selection Submitted!',
            'success_message' => 'Thank you. Your photos have been sent to our editor.',
            'back_url'        => route('transaksi.index')
        ];

        return redirect()->route('transaksi.view-select-for-edit', $transaksi)->with($redirectData);
    }

    // In app/Http/Controllers/TransaksiController.php

    public function viewSelectForPrint(Transaksi $transaksi)
    {
        try {
            // Eager load the packet and its print options relationship
            $transaksi->load('packet.printOptions');

            if (!$transaksi->packet) {
                return redirect()->back()->with('error', 'Transaction is not linked to a valid packet.');
            }

            // Get the print allowances from the packet (e.g., ['8R + Frame' => 2, '4R' => 5])
            $printAllowances = $transaksi->packet->printOptions->pluck('pivot.quantity', 'name')->toArray();

            // Get photos available for printing
            $rawPhotos = $this->getPhotoDirectoryData($transaksi, 'RAW');
            $resultPhotos = $this->getPhotoDirectoryData($transaksi, 'Result');
            $allPhotos = array_unique(array_merge($rawPhotos['urls'], $resultPhotos['urls']));

            // Get previously selected prints
            $selectedForPrint = SelectedPrint::where('transaction_id', $transaksi->transaction_id)
                ->get(['file_url', 'print_size']);

            return view('user.transaction.manage-print-photo', [
                'transaksi'        => $transaksi,
                'photoUrls'        => $allPhotos,
                'printAllowances'  => $printAllowances,
                'selectedForPrint' => $selectedForPrint,
                'pageTitle'        => 'Select Photos for Printing',
                'formAction'       => route('transaksi.handle-select-for-print', $transaksi),
            ]);
        } catch (\Exception $e) {
            // Catch exceptions for cases where a folder (like 'Result') might not exist yet
            return redirect()->back()->with('error', 'Could not load photos. Please ensure photos have been uploaded.');
        }
    }

    // NEW METHOD: Handles submission from the "Select for Print" page
    public function handleSelectForPrint(Request $request, Transaksi $transaksi)
    {
        $request->validate(['selected_photos' => 'nullable|array']);
        $selections = $request->input('selected_photos', []);

        // --- NEW: Validation against allowances ---
        $transaksi->load('packet.printOptions');
        $printAllowances = $transaksi->packet->printOptions->pluck('pivot.quantity', 'name')->toArray();
        $selectionCounts = array_count_values(array_filter($selections));

        foreach ($selectionCounts as $size => $count) {
            if (!isset($printAllowances[$size]) || $count > $printAllowances[$size]) {
                return redirect()->back()->withInput()->with('error', "You have selected too many photos for the size: {$size}.");
            }
        }
        // --- End Validation ---

        DB::transaction(function () use ($transaksi, $selections) {
            SelectedPrint::where('transaction_id', $transaksi->transaction_id)->delete();

            $dataToInsert = [];
            $linksToCreate = [];

            foreach ($selections as $url => $size) {
                if (!empty($size)) {
                    $dataToInsert[] = ['transaction_id' => $transaksi->transaction_id, 'file_url' => $url, 'print_size' => $size, 'created_at' => now(), 'updated_at' => now()];
                    $linksToCreate[$url] = $size;
                }
            }

            if (!empty($dataToInsert)) {
                SelectedPrint::insert($dataToInsert);
            }

            $folderName = str_replace('/', '_', $transaksi->receipt_code);
            $pilihCetakPath = storage_path("app/public/photos/{$folderName}/Pilih Cetak");
            File::cleanDirectory($pilihCetakPath);

            foreach ($linksToCreate as $url => $size) {
                $fileName = basename($url);
                $sourcePathRaw = storage_path("app/public/photos/{$folderName}/RAW/{$fileName}");
                $sourcePathResult = storage_path("app/public/photos/{$folderName}/Result/{$fileName}");
                $linkPath = "{$pilihCetakPath}/{$size} - {$fileName}";
                $sourcePath = File::exists($sourcePathResult) ? $sourcePathResult : $sourcePathRaw;

                if (File::exists($sourcePath) && !File::exists($linkPath)) {
                    File::link($sourcePath, $linkPath);
                }
            }
            $transaksi->update(['process_status' => 'Proses Cetak']);
        });

        $redirectData = [
            'success_title'   => 'Print Selection Saved!',
            'success_message' => 'Thank you. We have received your photos for printing.',
            'back_url'        => route('transaksi.index')
        ];

        return redirect()->route('transaksi.view-select-for-print', $transaksi)->with($redirectData);
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

/*     public function selectImage(Request $request, $transaction)
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
    } */

    public function viewResultPhotos(Transaksi $transaksi, Request $request)
    {
        try {
            // Ambil filter dari query string (default RAW)
            $filter = $request->get('filter', 'raw');

            // Tentukan nama filter untuk highlight tombol
            switch ($filter) {
                case "result":
                    $currentFilter = "Result";
                    break;
                case "pilih_cetak":
                    $currentFilter = "Pilih Cetak";
                    break;
                case "pilih_edit":
                    $currentFilter = "Pilih Edit";
                    break;
                default:
                    $currentFilter = "RAW";
                    break;
            }

            $photos = $this->getPhotoDirectoryData($transaksi, $currentFilter);
            return view('user.transaction.result-photo', [
                'transaksi' => $transaksi,
                'photoUrls' => $photos,
                'photoCount' => $photos['count'],
                'currentFilter' => $currentFilter
            ]);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function viewSelectionsForAdmin(Transaksi $transaksi)
    {
        try {
            // Fetch photos from the source directory
            $photoData = $this->getPhotoDirectoryData($transaksi, 'RAW');

            // Fetch the URLs that the user has selected
            $selectedUrls = SelectedPhoto::where('transaction_id', $transaksi->transaction_id)
                ->pluck('file_url')
                ->toArray();

            return view('admin.transaction.view-selections', [
                'transaksi' => $transaksi,
                'photoUrls' => $photoData['urls'],
                'selectedUrls' => $selectedUrls,
            ]);

        } catch (\Exception $e) {
            return redirect()->route('transaksi.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Mark the editing process as complete and transition to the next status.
     */
    public function completeEditing(Transaksi $transaksi)
    {
        // Ensure this can only be done when editing is in progress or just finished
        if (!in_array($transaksi->process_status, ['Proses Edit', 'Selesai Editing'])) {
            return redirect()->back()->with('error', 'This action is not allowed at the current status.');
        }

        try {
            // Check if the associated packet has any print options defined
            if ($transaksi->packet && $transaksi->packet->printOptions()->exists()) {
                $transaksi->process_status = 'Siap Cetak';
            } else {
                $transaksi->process_status = 'Selesai';
            }

            $transaksi->save();

            return redirect()->back()->with('success', 'Transaction status has been updated.');

        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Failed to update transaction status.');
        }
    }

    /**
     * Display a printer-friendly version of the invoice.
     */
    public function printInvoice(Transaksi $transaksi)
    {
        // Eager load the actual relationships needed for the invoice.
        // The 'combined_defaults' accessor will use these automatically.
        $transaksi->load(
            'packet.product',
            'packet.additionalDefaults.additional',
            'packet.printOptions.pivot',
            'additionals'
        );

        return view('invoices.print-template', ['transaksi' => $transaksi]);
    }
}
