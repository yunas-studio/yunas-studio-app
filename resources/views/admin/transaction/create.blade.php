@extends('layouts.master')
@section('title')
    Create Transaction
@endsection

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Transaksi',
        'pagetitle' => 'Transactions',
        'breadcrumbs' => [
            ['text' => 'Transactions', 'url' => route('transaksi.index')],
            ['text' => 'Create Transaction', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="font-size-14 mb-4"><i class="mdi mdi-arrow-right text-primary me-1"></i> Transaction Information</h5>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('transaksi.store') }}">
                        @csrf

                        {{-- Customer Name --}}
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                   id="customer_name" name="customer_name" placeholder="Enter Customer Name"
                                   value="{{ old('customer_name') }}" required>
                            <label for="customer_name">Customer Name</label>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Status Pembayaran --}}
                        <div class="form mb-4">
                            <label class="form-label d-block mb-2">Status Pembayaran :</label>
                            <div class="custom-radio form-check form-check-inline">
                                <input type="radio" id="status1" name="status" value="belum dibayar" class="form-check-input @error('status') is-invalid @enderror" {{ old('status', 'belum dibayar') == 'belum dibayar' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status1">Belum Dibayar</label>
                            </div>
                            <div class="custom-radio form-check form-check-inline">
                                <input type="radio" id="status2" name="status" value="dp" class="form-check-input @error('status') is-invalid @enderror" {{ old('status') == 'dp' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status2">DP</label>
                            </div>
                            <div class="custom-radio form-check form-check-inline">
                                <input type="radio" id="status3" name="status" value="sudah dibayar" class="form-check-input @error('status') is-invalid @enderror" {{ old('status') == 'sudah dibayar' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status3">Sudah Dibayar</label>
                            </div>
                            @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- Receipt Code (Auto-generated display) --}}
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="receipt_code_display" value="Will be auto-generated" readonly>
                            <label for="receipt_code_display">Receipt Code</label>
                        </div>

                        {{-- Booking Date --}}
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Booking Date</label>
                            <input class="form-control @error('booking_date') is-invalid @enderror" type="date"
                                   value="{{ old('booking_date', \Carbon\Carbon::today()->format('Y-m-d')) }}" id="booking_date" name="booking_date" required>
                            @error('booking_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Booking Time --}}
                        <div class="mb-3">
                            <label for="booking_time" class="form-label">Booking Time</label>
                            <select class="form-select @error('booking_time') is-invalid @enderror" id="booking_time" name="booking_time" required>
                                <option value="">Select a time slot</option>
                                @foreach($timeSlots as $slot)
                                    <option value="{{ $slot }}" {{ old('booking_time') == $slot ? 'selected' : '' }}
                                            {{ in_array($slot, $bookedSlotsToday ?? []) ? 'disabled' : '' }}>
                                        {{ $slot }} {{ in_array($slot, $bookedSlotsToday ?? []) ? '(Booked)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('booking_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Product Selection --}}
                        <div class="mb-3">
                            <label for="product_ids" class="form-label">Products</label>
                            <select multiple class="form-select @error('product_ids') is-invalid @enderror @error('product_ids.*') is-invalid @enderror"
                                    id="product_ids" name="product_ids[]">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ (is_array(old('product_ids')) && in_array($product->id, old('product_ids'))) ? 'selected' : '' }}>
                                        {{ $product->name }} (Rp {{ number_format($product->price, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('product_ids.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- Links --}}
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('temporary_link') is-invalid @enderror" id="temporary_link" name="temporary_link" placeholder="Temporary Link" value="{{ old('temporary_link') }}">
                            <label for="temporary_link">Temporary Link (e.g., https://...)</label>
                            @error('temporary_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('selected_photos') is-invalid @enderror" id="selected_photos" name="selected_photos" placeholder="Selected Photos Link" value="{{ old('selected_photos') }}">
                            <label for="selected_photos">Selected Photos Link (e.g., https://...)</label>
                            @error('selected_photos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('final_link') is-invalid @enderror" id="final_link" name="final_link" placeholder="Final Link" value="{{ old('final_link') }}">
                            <label for="final_link">Final Link (e.g., https://...)</label>
                            @error('final_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <button type="submit" class="btn btn-primary waves-effect waves-light w-md"><i class="mdi mdi-content-save me-1"></i> Save Transaction</button>
                            <a href="{{ route('transaksi.index') }}" class="btn btn-outline-danger waves-effect waves-light w-md"><i class="mdi mdi-close me-1"></i> Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#product_ids').select2({
                placeholder: "Select products",
                allowClear: true,
                theme: "bootstrap-5"
            });

            const timeSlots = @json($timeSlots); // Get all possible time slots

            function updateBookingTimes(selectedDate) {
                const bookingTimeSelect = $('#booking_time');
                const originalSelectedTime = bookingTimeSelect.val(); // Preserve selection if possible
                bookingTimeSelect.empty().append('<option value="">Select a time slot</option>'); // Clear existing options

                if (!selectedDate) {
                    // If no date selected, disable time select or show all slots as available (depends on desired UX)
                     timeSlots.forEach(function(slot) {
                        bookingTimeSelect.append(`<option value="${slot}">${slot}</option>`);
                    });
                    bookingTimeSelect.val(originalSelectedTime);
                    return;
                }

                $.ajax({
                    url: '{{ route("transaksi.getBookedSlots") }}',
                    type: 'GET',
                    data: { date: selectedDate },
                    success: function(bookedSlotsOnSelectedDate) {
                        timeSlots.forEach(function(slot) {
                            let isDisabled = bookedSlotsOnSelectedDate.includes(slot);
                            let optionText = slot;
                            if (isDisabled) {
                                optionText += ' (Booked)';
                            }
                            bookingTimeSelect.append(
                                `<option value="${slot}" ${isDisabled ? 'disabled' : ''}>${optionText}</option>`
                            );
                        });
                        // Try to re-select the previously selected time if it's still available
                        if (originalSelectedTime && !bookedSlotsOnSelectedDate.includes(originalSelectedTime)) {
                             bookingTimeSelect.val(originalSelectedTime);
                        } else {
                            bookingTimeSelect.val(''); // Reset if previous selection is now booked
                        }
                    },
                    error: function(xhr) {
                        console.error("Error fetching booked slots:", xhr.responseText);
                        // Fallback: enable all slots or show an error
                        timeSlots.forEach(function(slot) {
                            bookingTimeSelect.append(`<option value="${slot}">${slot}</option>`);
                        });
                        alert('Could not load available time slots. Please try again.');
                    }
                });
            }

            $('#booking_date').on('change', function() {
                updateBookingTimes($(this).val());
            });

            // Initial call if a date is already selected (e.g., from old input)
            if ($('#booking_date').val()) {
                // updateBookingTimes($('#booking_date').val()); // Already handled by controller for initial load
            }
        });
    </script>
@endsection
