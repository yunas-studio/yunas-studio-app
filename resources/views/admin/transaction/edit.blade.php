@extends('layouts.master')
@section('title')
    Edit Transaction
@endsection

@section('css')
    <style>
        .price-summary-card {
            position: sticky;
            top: 80px; /* Adjust based on your navbar height */
        }
    </style>
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

    <form method="POST" action="{{ route('transaksi.update', $transaksi->transaction_id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                         {{-- ICON ADDED HERE --}}
                         <h5 class="font-size-20 mb-4"><i class="mdi mdi-file-document-edit-outline text-primary me-1"></i> Edit Transaction Details</h5>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="receipt_code_display" value="{{ $transaksi->receipt_code }}" readonly>
                            <label for="receipt_code_display">Receipt Code</label>
                        </div>

                         <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                        id="customer_name" name="customer_name" placeholder="Enter Customer Name"
                                        value="{{ old('customer_name', $transaksi->customer_name) }}" required>
                                    <label for="customer_name">Customer Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form mb-3">
                                    <label class="form-label d-block mb-2">Payment Status :</label>
                                    <div class="pt-2">
                                        @foreach(['belum dibayar', 'dp', 'sudah dibayar'] as $status)
                                            <div class="form-check form-check-inline">
                                                <input type="radio" id="status_{{ $loop->iteration }}" name="status" value="{{ $status }}" class="form-check-input" {{ old('status', $transaksi->status) == $status ? 'checked' : '' }}>
                                                <label class="form-check-label" for="status_{{ $loop->iteration }}">{{ ucwords($status) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="process_status" class="form-label">Process Status</label>
                                    <select class="form-select" id="process_status" name="process_status" required>
                                        @foreach (['Siap Cetak', 'Proses Cetak', 'Selesai'] as $status)
                                            <option value="{{ $status }}" {{ old('process_status', $transaksi->process_status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="packet_id" class="form-label">Packet</label>
                                    <select class="form-select" id="packet_id" name="packet_id" required>
                                        @foreach($packets as $productName => $packetGroup)
                                            <optgroup label="{{ $productName }}">
                                                @foreach($packetGroup as $packet)
                                                    <option value="{{ $packet->id }}" data-price="{{ $packet->price }}" {{ old('packet_id', $transaksi->packet_id) == $packet->id ? 'selected' : '' }}>
                                                        {{ $packet->name }} (Rp {{ number_format($packet->price, 0, ',', '.') }})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                 <div class="mb-3">
                                    <label for="discount-input">Discount (Rp)</label>
                                    <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount-input" name="discount" value="{{ old('discount', $transaksi->discount) }}" min="0">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="font-size-20 mb-3"><i class="mdi mdi-plus-box-multiple text-primary me-1"></i> Additionals</h5>
                        <div id="additionals-container" class="mb-3"></div>
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label for="add_additional_select" class="form-label">Add More Additionals</label>
                                <div class="input-group">
                                    <select id="add_additional_select" class="form-select">
                                        <option value="">Choose an additional...</option>
                                        @foreach($all_additionals as $additional)
                                            <option value="{{ $additional->id }}" data-name="{{ $additional->name }}" data-price="{{ $additional->price }}">
                                                {{ $additional->name }} (Rp {{ number_format($additional->price, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-success" type="button" id="add-additional-btn">Add</button>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h5 class="font-size-20 mb-4"><i class="mdi mdi-link-variant text-primary me-1"></i> Delivery Links</h5>
                         <div class="form-floating mb-3">
                            <input type="url" class="form-control" id="temporary_link" name="temporary_link" placeholder="Temporary Link" value="{{ old('temporary_link', $transaksi->temporary_link) }}">
                            <label for="temporary_link">Temporary Link</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control" id="selected_photos" name="selected_photos" placeholder="Selected Photos Link" value="{{ old('selected_photos', $transaksi->selected_photos) }}">
                            <label for="selected_photos">Selected Photos Link</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control" id="final_link" name="final_link" placeholder="Final Link" value="{{ old('final_link', $transaksi->final_link) }}">
                            <label for="final_link">Final Link</label>
                        </div>

                        <hr>
                        <h5 class="font-size-20 mb-3"><i class="mdi mdi-pencil-outline text-primary me-1"></i> Transaction Note</h5>
                        <div class="mb-3">
                            <textarea class="form-control" id="note" name="note" rows="3" placeholder="Add an internal note for this transaction...">{{ old('note', $transaksi->note) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                 <div class="card price-summary-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Price Summary</h5>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <tbody>
                                    <tr>
                                        <td>Packet Price :</td>
                                        <td id="summary-packet-price" class="text-end fw-bold">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td>Additionals Price :</td>
                                        <td id="summary-additionals-price" class="text-end fw-bold">Rp 0</td>
                                    </tr>
                                     <tr>
                                        <td class="border-0">Subtotal :</td>
                                        <td id="summary-subtotal" class="text-end fw-bold border-0">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td class="border-0 pt-0">Discount :</td>
                                        <td id="summary-discount" class="border-0 pt-0 text-end text-danger">- Rp 0</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <th class="fs-5">Total Price :</th>
                                        <th id="summary-total-price" class="text-end fs-5">Rp 0</th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-grid mt-4">
                             <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light">Save Changes</button>
                        </div>
                         <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary d-block mt-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const additionalsContainer = document.getElementById('additionals-container');
    const addAdditionalSelect = document.getElementById('add_additional_select');
    const packetSelect = document.getElementById('packet_id');
    const discountInput = document.getElementById('discount-input');
    const addBtn = document.getElementById('add-additional-btn');

    const summaryPacketPriceEl = document.getElementById('summary-packet-price');
    const summaryAdditionalsPriceEl = document.getElementById('summary-additionals-price');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryDiscountEl = document.getElementById('summary-discount');
    const summaryTotalPriceEl = document.getElementById('summary-total-price');

    const existingAdditionals = @json($transaksi->additionals->mapWithKeys(function ($item) {
        return [$item->id => [
            'name' => $item->name,
            'price' => $item->pivot->price,
            'quantity' => $item->pivot->quantity
        ]];
    }));

    const formatCurrency = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);

    const updateSummary = () => {
        let packetPrice = parseFloat(packetSelect.options[packetSelect.selectedIndex].dataset.price) || 0;
        let additionalsPrice = 0;
        document.querySelectorAll('.additional-row').forEach(row => {
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            additionalsPrice += price * qty;
        });
        const subtotal = packetPrice + additionalsPrice;
        const discount = parseFloat(discountInput.value) || 0;
        const total = subtotal - discount;

        summaryPacketPriceEl.textContent = formatCurrency(packetPrice);
        summaryAdditionalsPriceEl.textContent = formatCurrency(additionalsPrice);
        summarySubtotalEl.textContent = formatCurrency(subtotal);
        summaryDiscountEl.textContent = `- ${formatCurrency(discount)}`;
        summaryTotalPriceEl.textContent = formatCurrency(total > 0 ? total : 0);
    };

    const addAdditionalRow = (id, name, price, quantity) => {
        if (document.getElementById(`additional-row-${id}`)) return;
        const template = `
            <div class="row align-items-center mb-2 additional-row" id="additional-row-${id}">
                <div class="col-md-5"><input type="text" class="form-control" value="${name}" readonly></div>
                <div class="col-md-3"><div class="input-group"><span class="input-group-text">Rp</span><input type="text" name="additionals[${id}][price]" class="form-control price-input" value="${price}" readonly></div></div>
                <div class="col-md-2"><input type="number" name="additionals[${id}][quantity]" class="form-control quantity-input" value="${quantity}" min="1"></div>
                <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger remove-additional-btn w-100">X</button></div>
            </div>`;
        if (additionalsContainer.querySelector('.text-muted')) additionalsContainer.innerHTML = '';
        additionalsContainer.insertAdjacentHTML('beforeend', template);
    };

    const populateExistingAdditionals = () => {
        additionalsContainer.innerHTML = '';
        if (Object.keys(existingAdditionals).length > 0) {
            for (const id in existingAdditionals) {
                const item = existingAdditionals[id];
                addAdditionalRow(id, item.name, item.price, item.quantity);
            }
        } else {
            additionalsContainer.innerHTML = '<p class="text-muted">No additionals for this transaction.</p>';
        }
        updateSummary();
    };

    addBtn.addEventListener('click', () => {
        const selected = addAdditionalSelect.options[addAdditionalSelect.selectedIndex];
        if (!selected.value) return;
        addAdditionalRow(selected.value, selected.dataset.name, selected.dataset.price, 1);
        addAdditionalSelect.value = '';
        updateSummary();
    });

    additionalsContainer.addEventListener('click', e => {
        if (e.target.classList.contains('remove-additional-btn')) {
            e.target.closest('.additional-row').remove();
            if (!additionalsContainer.querySelector('.additional-row')) {
                additionalsContainer.innerHTML = '<p class="text-muted">No additionals selected.</p>';
            }
            updateSummary();
        }
    });

    additionalsContainer.addEventListener('input', e => {
        if (e.target.classList.contains('quantity-input')) updateSummary();
    });
    
    packetSelect.addEventListener('change', updateSummary);
    discountInput.addEventListener('input', updateSummary);

    populateExistingAdditionals();
});
</script>
@endsection
