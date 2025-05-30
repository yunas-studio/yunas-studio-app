<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Product;
use App\Models\Booking; // Import Booking model
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod; // For generating time slots

class TransaksiController extends Controller
{
    // Helper function to generate time slots
    private function generateTimeSlots($startTime = '08:00', $endTime = '17:00', $intervalMinutes = 30)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $interval = "PT{$intervalMinutes}M"; // Period Time interval
        $period = new CarbonPeriod($start, $interval, $end);
        $slots = [];
        foreach ($period as $date) {
            $slots[] = $date->format('H:i');
        }
        return $slots;
    }

    // Helper function to get booked slots for a specific date
    private function getBookedSlotsForDate($date)
    {
        // Ensure $date is a Carbon instance or a string Carbon can parse
        $targetDate = Carbon::parse($date)->toDateString();

        return Booking::whereDate('booking_datetime', $targetDate)
            ->get()
            ->map(function ($booking) {
                return Carbon::parse($booking->booking_datetime)->format('H:i');
            })
            ->toArray();
    }


    public function index()
    {
        $belumDibayarCount = Transaksi::where('status', 'belum dibayar')->count();
        $dpCount = Transaksi::where('status', 'dp')->count();
        $sudahDibayarCount = Transaksi::where('status', 'sudah dibayar')->count();
        $search = request('search');

        $transactions = Transaksi::with(['products', 'booking']) // Eager load booking
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('receipt_code', 'like', "%$search%")
                      ->orWhere('customer_name', 'like', "%$search%");
                });
            })
            ->orderByDesc(
                Booking::select('booking_datetime')
                    ->whereColumn('transaksi.transaction_id', 'bookings.transaction_id')
                    ->latest() // Assuming latest booking if multiple, though it should be hasOne
            )
            ->paginate(10);

        return view('admin.transaction.transaksi', compact('transactions', 'belumDibayarCount', 'dpCount', 'sudahDibayarCount'));
    }

    public function create()
    {
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        $timeSlots = $this->generateTimeSlots();
        // For initial load, let's get booked slots for today.
        // This will be more dynamic with AJAX later if needed.
        $bookedSlotsToday = $this->getBookedSlotsForDate(Carbon::today());

        return view('admin.transaction.create', compact('products', 'timeSlots', 'bookedSlotsToday'));
    }

    // New method to fetch booked slots via AJAX
    public function getBookedSlots(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $date = $request->input('date');
        $bookedSlots = $this->getBookedSlotsForDate($date);
        return response()->json($bookedSlots);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'temporary_link' => ['nullable', 'url', 'max:255'],
            'selected_photos' => ['nullable', 'url', 'max:255'],
            'final_link' => ['nullable', 'url', 'max:255'],
            'booking_date' => ['required', 'date_format:Y-m-d'], // New field
            'booking_time' => ['required', 'date_format:H:i'], // New field
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['exists:products,id'],
        ]);

        $bookingDateTimeString = $validatedData['booking_date'] . ' ' . $validatedData['booking_time'];
        $bookingDateTime = Carbon::parse($bookingDateTimeString);

        // Check if the slot is already booked (server-side validation)
        $slotAlreadyBooked = Booking::where('booking_datetime', $bookingDateTime->format('Y-m-d H:i:s'))->exists();
        if ($slotAlreadyBooked) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'The selected time slot (' . $bookingDateTime->format('d M Y H:i') . ') is no longer available. Please choose another slot.');
        }

        $transaksi = null;
        DB::beginTransaction();

        try {
            $transaksi = Transaksi::create([
                'customer_name' => $validatedData['customer_name'],
                'status' => $validatedData['status'],
                'receipt_code' => 'TEMP-' . uniqid(), // Temporary
                'temporary_link' => $validatedData['temporary_link'],
                'selected_photos' => $validatedData['selected_photos'],
                'final_link' => $validatedData['final_link'],
            ]);

            // Create the booking
            $booking = $transaksi->booking()->create([
                'booking_datetime' => $bookingDateTime,
            ]);

            // Generate and update receipt_code
            $receiptDate = $bookingDateTime->format('Ymd');
            $finalReceiptCode = "INV/{$receiptDate}/{$transaksi->transaction_id}";
            $transaksi->receipt_code = $finalReceiptCode;
            $transaksi->save();

            if ($request->has('product_ids') && is_array($request->product_ids)) {
                $transaksi->products()->attach($request->product_ids);
            }

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction created successfully. Booked for ' . $bookingDateTime->format('d M Y H:i') . '. Receipt: ' . $finalReceiptCode);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Transaction store error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return redirect()->back()->withInput()->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $transaksi = Transaksi::with(['products', 'booking'])->findOrFail($id);
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        $selectedProductIds = $transaksi->products->pluck('id')->toArray();
        $timeSlots = $this->generateTimeSlots();

        $currentBookingDate = $transaksi->booking ? $transaksi->booking->booking_datetime->format('Y-m-d') : Carbon::today()->format('Y-m-d');
        $currentBookingTime = $transaksi->booking ? $transaksi->booking->booking_datetime->format('H:i') : null;

        // Get booked slots for the current booking's date, excluding the current transaction's booking
        $bookedSlotsForDate = Booking::whereDate('booking_datetime', $currentBookingDate)
            ->where('transaction_id', '!=', $transaksi->transaction_id) // Exclude current booking
            ->get()
            ->map(function ($booking) {
                return Carbon::parse($booking->booking_datetime)->format('H:i');
            })
            ->toArray();

        return view('admin.transaction.edit', compact(
            'transaksi',
            'products',
            'selectedProductIds',
            'timeSlots',
            'currentBookingDate',
            'currentBookingTime',
            'bookedSlotsForDate'
        ));
    }

    public function update(Request $request, string $id)
    {
        $transaksi = Transaksi::with('booking')->findOrFail($id);

        $validatedData = $request->validate([
            'customer_name' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'receipt_code' => ['required', 'string', 'max:50', 'unique:transaksi,receipt_code,' . $transaksi->transaction_id . ',transaction_id'],
            'temporary_link' => ['nullable', 'url', 'max:255'],
            'selected_photos' => ['nullable', 'url', 'max:255'],
            'final_link' => ['nullable', 'url', 'max:255'],
            'booking_date' => ['required', 'date_format:Y-m-d'],
            'booking_time' => ['required', 'date_format:H:i'],
            'isActive' => ['required', 'boolean'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['exists:products,id'],
        ]);

        $bookingDateTimeString = $validatedData['booking_date'] . ' ' . $validatedData['booking_time'];
        $newBookingDateTime = Carbon::parse($bookingDateTimeString);

        // Check if the new slot is already booked by another transaction
        $slotAlreadyBooked = Booking::where('booking_datetime', $newBookingDateTime->format('Y-m-d H:i:s'))
            ->where('transaction_id', '!=', $transaksi->transaction_id) // Exclude current transaction
            ->exists();

        if ($slotAlreadyBooked) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'The selected time slot (' . $newBookingDateTime->format('d M Y H:i') . ') is no longer available. Please choose another slot.');
        }

        DB::beginTransaction();
        try {
            // Update Transaksi details (excluding receipt_code if it depends on date)
            $transaksi->customer_name = $validatedData['customer_name'];
            $transaksi->status = $validatedData['status'];
            $transaksi->temporary_link = $validatedData['temporary_link'];
            $transaksi->selected_photos = $validatedData['selected_photos'];
            $transaksi->final_link = $validatedData['final_link'];
            $transaksi->isActive = $validatedData['isActive'];
            // Do not update receipt_code directly here if it needs regeneration based on new booking_date

            // Update or create booking
            if ($transaksi->booking) {
                $transaksi->booking->booking_datetime = $newBookingDateTime;
                $transaksi->booking->save();
            } else {
                // Should not happen if booking is integral, but as a fallback
                $transaksi->booking()->create(['booking_datetime' => $newBookingDateTime]);
                $transaksi->load('booking'); // Reload booking relationship
            }

            // Regenerate receipt_code if booking_date changed
            // Compare old booking date with new one
            $oldBookingDate = $transaksi->booking ? Carbon::parse($transaksi->getOriginal('booking')['booking_datetime'] ?? $transaksi->booking->booking_datetime)->format('Ymd') : null;
            $newBookingDateFormatted = $newBookingDateTime->format('Ymd');

            if ($oldBookingDate !== $newBookingDateFormatted || $validatedData['receipt_code'] !== "INV/{$newBookingDateFormatted}/{$transaksi->transaction_id}") {
                 $transaksi->receipt_code = "INV/{$newBookingDateFormatted}/{$transaksi->transaction_id}";
            } else {
                // If date hasn't changed, allow submitted receipt_code (validated for uniqueness)
                 $transaksi->receipt_code = $validatedData['receipt_code'];
            }
            $transaksi->save();


            if ($request->has('product_ids') && is_array($request->product_ids)) {
                $transaksi->products()->sync($request->product_ids);
            } else {
                $transaksi->products()->detach();
            }
            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction updated successfully. New booking: ' . $newBookingDateTime->format('d M Y H:i'));

        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Transaction update error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $transaksi = Transaksi::findOrFail($id);
            // Booking will be deleted by cascade if set up correctly in migration
            // $transaksi->booking()->delete(); // Or explicitly delete if cascade is not trusted/set
            $transaksi->products()->detach();
            $transaksi->delete();
            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction and its booking deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('transaksi.index')->with('error', 'Failed to delete transaction: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $transaksi = Transaksi::findOrFail($id); // Use findOrFail
        $transaksi->isActive = !$transaksi->isActive;
        $transaksi->save();
        return redirect()->back()->with('success', 'Transaction status updated successfully.');
    }
}
