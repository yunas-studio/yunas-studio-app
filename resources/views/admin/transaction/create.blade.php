@extends('layouts.master')
@section('title')
    Create Transaction
@endsection

@section('css')
    <style>
        .price-summary-card {
            position: sticky;
            top: 80px; /* Adjust based on your navbar height */
        }
        .section-title {
            font-size: 1.1rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Transaksi',
        'pagetitle' => 'Transactions',
        'breadcrumbs' => [['text' => 'Transactions', 'url' => route('transaksi.index')], ['text' => 'Create Transaction', 'url' => '']]
    ])
    @endcomponent

    <form method="POST" action="{{ route('transaksi.store') }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="section-title"><i class="mdi mdi-file-document-edit-outline text-primary me-1"></i> Transaction Details</h5>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="customer_name">Customer Name</label>
                                    <input type="text" class="form-control form-control-sm" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="phone_number">Phone Number</label>
                                    <input type="text" class="form-control form-control-sm" id="phone_number" name="phone_number" value="{{ old('phone_number') }}">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label d-block mb-2">Payment Status</label>
                                    <div class="pt-2">
                                        @foreach(['belum dibayar', 'dp', 'sudah dibayar'] as $status)
                                            <div class="form-check form-check-inline">
                                                <input type="radio" id="status_{{ $loop->iteration }}" name="status" value="{{ $status }}" class="form-check-input" {{ old('status', 'belum dibayar') == $status ? 'checked' : '' }}>
                                                <label class="form-check-label" for="status_{{ $loop->iteration }}">{{ ucwords($status) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="packet_id" class="form-label">Packet</label>
                                    <select class="form-select @error('packet_id') is-invalid @enderror" id="packet_id" name="packet_id" required>
                                        <option value="" data-price="0" disabled selected>-- Select a Packet --</option>
                                        @foreach($packets as $productName => $packetGroup)
                                            <optgroup label="{{ $productName }}">
                                                @foreach($packetGroup as $packet)
                                                    <option value="{{ $packet->id }}" data-price="{{ $packet->price }}" {{ old('packet_id') == $packet->id ? 'selected' : '' }}>
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
                                    <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount-input" name="discount" value="{{ old('discount', 0) }}" min="0">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-check-all text-primary me-1"></i> Included Additionals</h5>
                        <div id="included-additionals-container" class="mb-3">
                            <p class="text-muted">Select a packet to see its included items.</p>
                        </div>

                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-plus-box-multiple text-primary me-1"></i> Extra Additionals</h5>
                        <div id="extra-additionals-container" class="mb-3">
                            {{-- JS populates this --}}
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label for="add_additional_select" class="form-label">Add Item</label>
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
                        <h5 class="section-title"><i class="mdi mdi-link-variant text-primary me-1"></i> Delivery Links</h5>
                        <div class="row">
                            <div class="col-md-4"><div class="form-floating mb-3"><input type="url" class="form-control" name="temporary_link" placeholder="Temporary Link" value="{{ old('temporary_link') }}"><label>Temporary Link</label></div></div>
                            <div class="col-md-4"><div class="form-floating mb-3"><input type="url" class="form-control" name="selected_photos" placeholder="Selected Photos Link" value="{{ old('selected_photos') }}"><label>Selected Photos Link</label></div></div>
                            <div class="col-md-4"><div class="form-floating mb-3"><input type="url" class="form-control" name="final_link" placeholder="Final Link" value="{{ old('final_link') }}"><label>Final Link</label></div></div>
                        </div>

                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-pencil-outline text-primary me-1"></i> Transaction Note</h5>
                        <div class="mb-3">
                            <textarea class="form-control" id="note" name="note" rows="3" placeholder="Add an internal note for this transaction...">{{ old('note') }}</textarea>
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
                                    <tr><td>Packet Price :</td><td id="summary-packet-price" class="text-end fw-bold">Rp 0</td></tr>
                                    <tr><td>Extra Additionals :</td><td id="summary-additionals-price" class="text-end fw-bold">Rp 0</td></tr>
                                    <tr><td class="border-0">Subtotal :</td><td id="summary-subtotal" class="text-end fw-bold border-0">Rp 0</td></tr>
                                    <tr><td class="border-0 pt-0">Discount :</td><td id="summary-discount" class="border-0 pt-0 text-end text-danger">- Rp 0</td></tr>
                                    <tr class="bg-light"><th class="fs-5">Total Price :</th><th id="summary-total-price" class="text-end fs-5">Rp 0</th></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-grid mt-4">
                             <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light"><i class="mdi mdi-content-save me-1"></i> Save Transaction</button>
                        </div>
                         <a href="{{ route('transaksi.index') }}" class="btn btn-light d-block mt-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
{{-- The JavaScript is unchanged from the previous step --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const includedContainer = document.getElementById('included-additionals-container');
    const extraContainer = document.getElementById('extra-additionals-container');
    const addSelect = document.getElementById('add_additional_select');
    const packetSelect = document.getElementById('packet_id');
    const discountInput = document.getElementById('discount-input');
    const addBtn = document.getElementById('add-additional-btn');

    const summaryPacketEl = document.getElementById('summary-packet-price');
    const summaryAdditionalsEl = document.getElementById('summary-additionals-price');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryDiscountEl = document.getElementById('summary-discount');
    const summaryTotalEl = document.getElementById('summary-total-price');

    const formatCurrency = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

    function updateSummary() {
        const packetPrice = parseFloat(packetSelect.options[packetSelect.selectedIndex].dataset.price) || 0;
        let extraAdditionalsPrice = 0;
        document.querySelectorAll('.extra-additional-row').forEach(row => {
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            extraAdditionalsPrice += price * qty;
        });
        const subtotal = packetPrice + extraAdditionalsPrice;
        const discount = parseFloat(discountInput.value) || 0;
        const total = subtotal - discount;

        summaryPacketEl.textContent = formatCurrency(packetPrice);
        summaryAdditionalsEl.textContent = formatCurrency(extraAdditionalsPrice);
        summarySubtotalEl.textContent = formatCurrency(subtotal);
        summaryDiscountEl.textContent = `- ${formatCurrency(discount)}`;
        summaryTotalEl.textContent = formatCurrency(total > 0 ? total : 0);
    }

    function addExtraRow(id, name, price, quantity) {
        if (document.getElementById(`extra-additional-row-${id}`)) return;
        const template = `
            <div class="row align-items-center mb-2 extra-additional-row" id="extra-additional-row-${id}">
                <div class="col-md-5"><input type="text" class="form-control" value="${name}" readonly></div>
                <div class="col-md-3"><div class="input-group"><span class="input-group-text">Rp</span><input type="text" name="additionals[${id}][price]" class="form-control price-input" value="${price}" readonly></div></div>
                <div class="col-md-2"><input type="number" name="additionals[${id}][quantity]" class="form-control quantity-input" value="${quantity}" min="1"></div>
                <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger remove-additional-btn w-100">X</button></div>
            </div>`;
        if (extraContainer.querySelector('.text-muted')) extraContainer.innerHTML = '';
        extraContainer.insertAdjacentHTML('beforeend', template);
    }

    packetSelect.addEventListener('change', function() {
        const packetId = this.value;
        includedContainer.innerHTML = '<p class="text-muted">Loading...</p>';
        if (!packetId) return;

        fetch(`/packets/${packetId}/default-additionals`)
            .then(response => response.json())
            .then(data => {
                includedContainer.innerHTML = '';
                if (data.length === 0) {
                    includedContainer.innerHTML = '<p class="text-muted">This packet has no included additionals.</p>';
                } else {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'included-item d-inline-block me-2 mb-2';
                        div.textContent = `${item.quantity}x ${item.additional.name}`;
                        includedContainer.appendChild(div);
                    });
                }
                updateSummary();
            })
            .catch(() => includedContainer.innerHTML = '<p class="text-danger">Could not load included items.</p>');
    });

    addBtn.addEventListener('click', () => {
        const selected = addSelect.options[addSelect.selectedIndex];
        if (!selected.value) return;
        addExtraRow(selected.value, selected.dataset.name, selected.dataset.price, 1);
        addSelect.value = '';
        updateSummary();
    });

    extraContainer.addEventListener('click', e => {
        if (e.target.classList.contains('remove-additional-btn')) {
            e.target.closest('.extra-additional-row').remove();
            updateSummary();
        }
    });

    extraContainer.addEventListener('input', e => {
        if (e.target.classList.contains('quantity-input')) updateSummary();
    });

    packetSelect.addEventListener('change', updateSummary);
    discountInput.addEventListener('input', updateSummary);
    updateSummary();
});
</script>
@endsection
