@extends('layouts.master')
@section('title')
    Edit Transaction
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
            ['text' => 'Edit Transaction #' . $transaksi->receipt_code, 'url' => '']
        ]
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="font-size-14 mb-4"><i class="mdi mdi-arrow-right text-primary me-1"></i> Edit Transaction Information</h5>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('transaksi.update', $transaksi->transaction_id) }}">
                        @csrf
                        @method('PUT')

                        {{-- Customer Name --}}
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                   id="customer_name" name="customer_name" placeholder="Enter Customer Name"
                                   value="{{ old('customer_name', $transaksi->customer_name) }}" required>
                            <label for="customer_name">Customer Name</label>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Status Pembayaran --}}
                        <div class="form mb-4">
                            <label class="form-label d-block mb-2">Status Pembayaran :</label>
                            <div class="custom-radio form-check form-check-inline">
                                <input type="radio" id="status1" name="status" value="belum dibayar" class="form-check-input @error('status') is-invalid @enderror" {{ old('status', $transaksi->status) == 'belum dibayar' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status1">Belum Dibayar</label>
                            </div>
                            <div class="custom-radio form-check form-check-inline">
                                <input type="radio" id="status2" name="status" value="dp" class="form-check-input @error('status') is-invalid @enderror" {{ old('status', $transaksi->status) == 'dp' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status2">DP</label>
                            </div>
                            <div class="custom-radio form-check form-check-inline">
                                <input type="radio" id="status3" name="status" value="sudah dibayar" class="form-check-input @error('status') is-invalid @enderror" {{ old('status', $transaksi->status) == 'sudah dibayar' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status3">Sudah Dibayar</label>
                            </div>
                            @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- Receipt Code (Display, potentially readonly or carefully validated if editable) --}}
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('receipt_code') is-invalid @enderror"
                                   id="receipt_code" name="receipt_code"
                                   value="{{ old('receipt_code', $transaksi->receipt_code) }}" required {{-- Consider 'readonly' if it should not be changed after creation --}}>
                            <label for="receipt_code">Receipt Code</label>
                            @error('receipt_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Booking Date --}}
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Booking Date</label>
                            <input class="form-control @error('booking_date') is-invalid @enderror" type="date"
                                   value="{{ old('booking_date', $currentBookingDate) }}" id="booking_date" name="booking_date" required>
                            @error('booking_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Booking Time --}}
                        <div class="mb-3">
                            <label for="booking_time" class="form-label">Booking Time</label>
                            <select class="form-select @error('booking_time') is-invalid @enderror" id="booking_time" name="booking_time" required>
                                <option value="">Select a time slot</option>
                                @foreach($timeSlots as $slot)
                                    <option value="{{ $slot }}"
                                            {{ old('booking_time', $currentBookingTime) == $slot ? 'selected' : '' }}
                                            {{ in_array($slot, $bookedSlotsForDate ?? []) && $slot != $currentBookingTime ? 'disabled' : '' }}>
                                        {{ $slot }} {{ in_array($slot, $bookedSlotsForDate ?? []) && $slot != $currentBookingTime ? '(Booked)' : '' }}
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
                                    <option value="{{ $product->id }}" {{ (is_array(old('product_ids')) && in_array($product->id, old('product_ids'))) || (empty(old('product_ids')) && isset($selectedProductIds) && in_array($product->id, $selectedProductIds)) ? 'selected' : '' }}>
                                        {{ $product->name }} (Rp {{ number_format($product->price, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('product_ids.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- Links --}}
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('temporary_link') is-invalid @enderror" id="temporary_link" name="temporary_link" placeholder="Temporary Link" value="{{ old('temporary_link', $transaksi->temporary_link) }}">
                            <label for="temporary_link">Temporary Link</label>
                            @error('temporary_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('selected_photos') is-invalid @enderror" id="selected_photos" name="selected_photos" placeholder="Selected Photos Link" value="{{ old('selected_photos', $transaksi->selected_photos) }}">
                            <label for="selected_photos">Selected Photos Link</label>
                            @error('selected_photos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('final_link') is-invalid @enderror" id="final_link" name="final_link" placeholder="Final Link" value="{{ old('final_link', $transaksi->final_link) }}">
                            <label for="final_link">Final Link</label>
                            @error('final_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Active Status --}}
                        <div class="form-group mb-3">
                            <label class="form-label d-block">Active Status</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="isActive" value="0">
                                <input type="checkbox" class="form-check-input @error('isActive') is-invalid @enderror" id="isActive" name="isActive" value="1" {{ old('isActive', $transaksi->isActive) == 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Set as Active</label>
                                @error('isActive') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <button type="submit" class="btn btn-primary waves-effect waves-light w-md"><i class="mdi mdi-content-save me-1"></i> Save Changes</button>
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

            const timeSlots = @json($timeSlots);
            const currentTransactionId = {{ $transaksi->transaction_id }}; // For edit page context
            const initialBookingTime = "{{ old('booking_time', $currentBookingTime) }}"; // Current or old booking time

            function updateBookingTimes(selectedDate) {
                const bookingTimeSelect = $('#booking_time');
                bookingTimeSelect.empty().append('<option value="">Select a time slot</option>');

                if (!selectedDate) {
                     timeSlots.forEach(function(slot) {
                        bookingTimeSelect.append(`<option value="${slot}">${slot}</option>`);
                    });
                    if(initialBookingTime) bookingTimeSelect.val(initialBookingTime);
                    return;
                }

                $.ajax({
                    url: '{{ route("transaksi.getBookedSlots") }}', // Same AJAX endpoint
                    type: 'GET',
                    data: { date: selectedDate, current_transaction_id: currentTransactionId }, // Optionally send current_transaction_id if your backend needs to exclude it
                    success: function(bookedSlotsOnSelectedDate) {
                        timeSlots.forEach(function(slot) {
                            // A slot is disabled if it's in bookedSlotsOnSelectedDate AND it's not the initialBookingTime for this transaction
                            let isDisabled = bookedSlotsOnSelectedDate.includes(slot) && slot !== initialBookingTime;
                            let optionText = slot;
                            if (bookedSlotsOnSelectedDate.includes(slot) && slot !== initialBookingTime) {
                                optionText += ' (Booked)';
                            }
                            bookingTimeSelect.append(
                                `<option value="${slot}" ${isDisabled ? 'disabled' : ''}>${optionText}</option>`
                            );
                        });
                        // Set the selected value to the initial booking time or the old input if available
                        if (initialBookingTime) {
                             bookingTimeSelect.val(initialBookingTime);
                        } else {
                            bookingTimeSelect.val('');
                        }
                    },
                    error: function(xhr) {
                        console.error("Error fetching booked slots:", xhr.responseText);
                        timeSlots.forEach(function(slot) {
                            bookingTimeSelect.append(`<option value="${slot}">${slot}</option>`);
                        });
                        if(initialBookingTime) bookingTimeSelect.val(initialBookingTime);
                        alert('Could not load available time slots. Please try again.');
                    }
                });
            }

            $('#booking_date').on('change', function() {
                updateBookingTimes($(this).val());
            });

            // Initial call to populate time slots based on current booking date
            if ($('#booking_date').val()) {
                updateBookingTimes($('#booking_date').val());
            }
        });
    </script>
@endsection
