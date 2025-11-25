@extends('layouts.master')
@section('title')
    Create Transaction
@endsection

@section('css')
    <style>
        .price-summary-card {
            position: sticky;
            top: 80px;
        }
        .section-title {
            font-size: 1.1rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .product-packet-container {
            display: none;
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
                                    <input type="text" class="form-control form-control-sm" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label d-block mb-2">Payment Status</label>
                                    <div class="pt-2">
                                        @foreach(['belum dibayar', 'dp', 'sudah dibayar'] as $status)
                                            <div class="form-check form-check-inline">
                                                <input type="radio" id="status_{{ $loop->iteration }}" name="status" value="{{ $status }}" class="form-check-input payment-status-radio" {{ old('status', 'belum dibayar') == $status ? 'checked' : '' }}>
                                                <label class="form-check-label" for="status_{{ $loop->iteration }}">{{ ucwords($status) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5" id="dp-amount-container" style="display: none;">
                                <div class="mb-3">
                                    <label for="dp_amount">DP Amount (Rp)</label>
                                    <input type="number" class="form-control form-control-sm" id="dp_amount" name="dp_amount" value="{{ old('dp_amount') }}" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">Product</label>
                                    <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                                        <option value="" disabled selected>-- Select a Product --</option>
                                        @foreach($packets as $productName => $packetGroup)
                                            <option value="{{ $packetGroup->first()->product_id }}"
                                                    data-product-name="{{ $productName }}">
                                                {{ $productName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="packet_id" class="form-label">Packet</label>
                                    <select class="form-select @error('packet_id') is-invalid @enderror" id="packet_id" name="packet_id" required disabled>
                                        <option value="" data-price="0" disabled selected>-- Select a Packet --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="discount-input">Discount (Rp)</label>
                                    <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount-input" name="discount" value="{{ old('discount', 0) }}" min="0">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="section-title"><i class="mdi mdi-check-all text-primary me-1"></i> Included Additionals</h5>
                        <div id="included-additionals-container" class="mb-3">
                            <p class="text-muted">Select a product and packet to see its included items.</p>
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

                                <tr class="bg-light" id="summary-total-row">
                                    <th class="fs-5">Total Price :</th>
                                    <th id="summary-total-price" class="text-end fs-5">Rp 0</th>
                                </tr>

                                <tr id="summary-dp-row" style="display: none;">
                                    <td class="fw-bold">DP Paid :</td>
                                    <td id="summary-dp-paid" class="text-end fw-bold">Rp 0</td>
                                </tr>
                                <tr class="bg-light" id="summary-remaining-row" style="display: none;">
                                    <th class="fs-5">Remaining :</th>
                                    <th id="summary-remaining-balance" class="text-end fs-5">Rp 0</th>
                                </tr>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form Elements
            const productSelect = document.getElementById('product_id');
            const packetSelect = document.getElementById('packet_id');
            const discountInput = document.getElementById('discount-input');
            const includedContainer = document.getElementById('included-additionals-container');
            const extraContainer = document.getElementById('extra-additionals-container');
            const addSelect = document.getElementById('add_additional_select');
            const addBtn = document.getElementById('add-additional-btn');
            const statusRadios = document.querySelectorAll('.payment-status-radio');
            const dpAmountContainer = document.getElementById('dp-amount-container');
            const dpAmountInput = document.getElementById('dp_amount');

            // Summary Card Elements
            const summary = {
                packetEl: document.getElementById('summary-packet-price'),
                additionalsEl: document.getElementById('summary-additionals-price'),
                subtotalEl: document.getElementById('summary-subtotal'),
                discountEl: document.getElementById('summary-discount'),
                totalPriceEl: document.getElementById('summary-total-price'),
                dpPaidEl: document.getElementById('summary-dp-paid'),
                remainingBalanceEl: document.getElementById('summary-remaining-balance'),
                totalRow: document.getElementById('summary-total-row'),
                dpRow: document.getElementById('summary-dp-row'),
                remainingRow: document.getElementById('summary-remaining-row'),
            };

            const formatCurrency = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

            // Product and Packet data from server
            const productPackets = {!! json_encode($packets) !!};

            function updateSummary() {
                const packetPrice = parseFloat(packetSelect.options[packetSelect.selectedIndex]?.dataset.price) || 0;
                let extraAdditionalsPrice = 0;
                document.querySelectorAll('.extra-additional-row').forEach(row => {
                    const price = parseFloat(row.querySelector('.price-input').value) || 0;
                    const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
                    extraAdditionalsPrice += price * qty;
                });

                const subtotal = packetPrice + extraAdditionalsPrice;
                const discount = parseFloat(discountInput.value) || 0;
                const total = subtotal - discount > 0 ? subtotal - discount : 0;
                const dpAmount = parseFloat(dpAmountInput.value) || 0;
                const remaining = total - dpAmount;
                const selectedStatus = document.querySelector('.payment-status-radio:checked')?.value;

                summary.packetEl.textContent = formatCurrency(packetPrice);
                summary.additionalsEl.textContent = formatCurrency(extraAdditionalsPrice);
                summary.subtotalEl.textContent = formatCurrency(subtotal);
                summary.discountEl.textContent = `- ${formatCurrency(discount)}`;
                summary.totalPriceEl.textContent = formatCurrency(total);

                if (selectedStatus === 'dp') {
                    summary.totalRow.style.display = 'none';
                    summary.dpRow.style.display = '';
                    summary.remainingRow.style.display = '';
                    summary.dpPaidEl.textContent = formatCurrency(dpAmount);
                    summary.remainingBalanceEl.textContent = formatCurrency(remaining > 0 ? remaining : 0);
                } else {
                    summary.totalRow.style.display = '';
                    summary.dpRow.style.display = 'none';
                    summary.remainingRow.style.display = 'none';
                }
            }

            function addExtraRow(id, name, price, quantity = 1) {
                if (document.getElementById(`extra-additional-row-${id}`)) return;

                const rowId = `extra-additional-row-${id}`;
                const template = `
            <div class="row align-items-center mb-2 extra-additional-row" id="${rowId}">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" value="${name}" readonly>
                    <input type="hidden" name="additionals[${id}][id]" value="${id}">
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="additionals[${id}][price]" class="form-control price-input" value="${price}" min="0" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="number" name="additionals[${id}][quantity]" class="form-control form-control-sm quantity-input" value="${quantity}" min="1">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-danger remove-additional-btn w-100" onclick="document.getElementById('${rowId}').remove(); updateSummary();">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>`;

                if (extraContainer.querySelector('.text-muted')) {
                    extraContainer.innerHTML = '';
                }
                extraContainer.insertAdjacentHTML('beforeend', template);
                updateSummary();
            }

            function toggleDpField() {
                const selectedStatus = document.querySelector('.payment-status-radio:checked')?.value;
                dpAmountContainer.style.display = selectedStatus === 'dp' ? 'block' : 'none';
                if (selectedStatus !== 'dp') dpAmountInput.value = '';
                updateSummary();
            }

            function fetchAndDisplayDefaults() {
                const packetId = packetSelect.value;
                includedContainer.innerHTML = '<p class="text-muted">Loading...</p>';

                if (!packetId) {
                    includedContainer.innerHTML = '<p class="text-muted">Select a product and packet to see its included items.</p>';
                    return;
                }

                fetch(`/packets/${packetId}/default-additionals`)
                    .then(response => response.json())
                    .then(data => {
                        includedContainer.innerHTML = '';
                        if (data.length === 0) {
                            includedContainer.innerHTML = '<p class="text-muted">This packet has no included additionals.</p>';
                        } else {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'included-item d-inline-block border rounded-pill px-2 py-1 me-2 mb-2';
                                div.textContent = `${item.quantity}x ${item.name}`;
                                includedContainer.appendChild(div);
                            });
                        }
                        updateSummary();
                    })
                    .catch(() => {
                        includedContainer.innerHTML = '<p class="text-danger">Could not load included items.</p>';
                    });
            }

            // Update packet options when product changes
            productSelect.addEventListener('change', function() {
                const productId = this.value;
                packetSelect.innerHTML = '<option value="" data-price="0" disabled selected>-- Select a Packet --</option>';

                if (productId) {
                    const productName = this.options[this.selectedIndex].dataset.productName;
                    const packets = productPackets[productName];

                    packets.forEach(packet => {
                        const option = document.createElement('option');
                        option.value = packet.id;
                        option.textContent = `${packet.name} (Rp ${new Intl.NumberFormat('id-ID').format(packet.price)})`;
                        option.dataset.price = packet.price;
                        packetSelect.appendChild(option);
                    });

                    packetSelect.disabled = false;
                } else {
                    packetSelect.disabled = true;
                }

                // Reset related fields
                includedContainer.innerHTML = '<p class="text-muted">Select a packet to see its included items.</p>';
                updateSummary();
            });

            // Event Listeners
            packetSelect.addEventListener('change', fetchAndDisplayDefaults);
            addBtn.addEventListener('click', () => {
                const selected = addSelect.options[addSelect.selectedIndex];
                if (selected.value) {
                    addExtraRow(selected.value, selected.dataset.name, selected.dataset.price);
                    addSelect.value = '';
                }
            });

            extraContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input')) {
                    updateSummary();
                }
            });

            statusRadios.forEach(radio => radio.addEventListener('change', toggleDpField));
            discountInput.addEventListener('input', updateSummary);
            dpAmountInput.addEventListener('input', updateSummary);

            // Initialize form
            toggleDpField();
            updateSummary();
        });
    </script>
@endsection
