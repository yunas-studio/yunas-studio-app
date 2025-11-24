@extends('layouts.master')
@section('title', 'View Photo Gallery')

@section('css')
    <style>
        .photo-card {
            border: 2px solid transparent;
            border-radius: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .photo-card.selected-edit {
            border-color: #556ee6; /* Blue border for edit */
        }
        .photo-card.selected-print {
            border-color: #34c38f; /* Green border for print */
        }
        .photo-card.selected-both {
            /* A gradient or different style for photos selected for both */
            border-image: linear-gradient(45deg, #556ee6, #34c38f) 1;
        }
        .image-container {
            overflow: hidden;
            border-top-left-radius: calc(0.5rem - 2px);
            border-top-right-radius: calc(0.5rem - 2px);
        }
        .fixed-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            background-color: #e9ecef;
            transition: transform 0.3s ease;
        }
        .photo-card:hover .fixed-image {
            transform: scale(1.05);
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Photo Gallery',
        'pagetitle' => 'Transactions',
        'breadcrumbs' => [
            ['text' => 'Transactions', 'url' => route('transaksi.index')],
            ['text' => 'View Gallery', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="container-fluid bg-white py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h2 class="mb-1">Gallery for #{{ $transaksi->receipt_code }}</h2>
                    <p class="text-muted mb-1">Customer: <strong>{{ $transaksi->customer_name }}</strong></p>
                    
                    {{-- INFORMASI & DETAIL PAKET --}}
                    @if($transaksi->packet)
                        <div class="mt-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-primary font-size-12 me-2">{{ $transaksi->packet->name }}</span>
                                @if($transaksi->packet->product)
                                    <span class="text-muted small me-3"><i class="bx bx-category me-1"></i>{{ $transaksi->packet->product->name }}</span>
                                @endif
                                
                                {{-- Trigger Collapse --}}
                                <a class="text-primary small text-decoration-none cursor-pointer" data-bs-toggle="collapse" href="#packetContent" role="button" aria-expanded="false">
                                    <i class="bx bx-info-circle me-1"></i>View Item Details
                                </a>
                            </div>

                            {{-- TABEL DETAIL PAKET & ADDITIONAL (COLLAPSIBLE) --}}
                            <div class="collapse" id="packetContent">
                                <div class="card border shadow-none mb-0" style="max-width: 500px;">
                                    <div class="card-header bg-light py-2 px-3">
                                        <h6 class="mb-0 font-size-13 text-dark">Transaction Items</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-striped mb-0 font-size-13">
                                            <tbody>
                                                {{-- 1. Included Prints (Cetak Bawaan Paket) --}}
                                                @if($transaksi->packet->printOptions->isNotEmpty())
                                                    <tr><td colspan="2" class="bg-soft-light px-3 py-1 fw-bold text-muted small">Included Prints</td></tr>
                                                    @foreach($transaksi->packet->printOptions as $print)
                                                        <tr>
                                                            <td class="px-3 py-2 ps-4"><i class="bx bx-printer me-2 text-secondary"></i> Cetak {{ $print->name }}</td>
                                                            <td class="text-center py-2" width="60">x{{ $print->pivot->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif

                                                {{-- 2. Additional Defaults (Barang Bawaan Paket) --}}
                                                @if($transaksi->packet->additionalDefaults->isNotEmpty())
                                                    <tr><td colspan="2" class="bg-soft-light px-3 py-1 fw-bold text-muted small">Included Items</td></tr>
                                                    @foreach($transaksi->packet->additionalDefaults as $default)
                                                        <tr>
                                                            <td class="px-3 py-2 ps-4"><i class="bx bx-check-circle me-2 text-secondary"></i> {{ $default->additional->name }}</td>
                                                            <td class="text-center py-2" width="60">x{{ $default->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif

                                                {{-- 3. Transaction Additionals (Extra Berbayar) --}}
                                                @if($transaksi->additionals->isNotEmpty())
                                                    <tr><td colspan="2" class="bg-soft-warning px-3 py-1 fw-bold text-warning small">Extra Add-ons</td></tr>
                                                    @foreach($transaksi->additionals as $additional)
                                                        <tr>
                                                            <td class="px-3 py-2 ps-4">
                                                                <i class="bx bx-plus me-2 text-warning"></i> {{ $additional->name }}
                                                            </td>
                                                            <td class="text-center py-2" width="60">x{{ $additional->pivot->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif

                                                @if($transaksi->packet->printOptions->isEmpty() && $transaksi->packet->additionalDefaults->isEmpty() && $transaksi->additionals->isEmpty())
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted fst-italic py-2">No details available.</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Transactions
                    </a>
                </div>
            </div>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#editPhotos" role="tab">
                        <span class="d-block d-sm-none"><i class="fas fa-edit"></i></span>
                        <span class="d-none d-sm-block">Edit Selections</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#printPhotos" role="tab">
                        <span class="d-block d-sm-none"><i class="fas fa-print"></i></span>
                        <span class="d-none d-sm-block">Print Selections</span>
                    </a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content p-3 text-muted">
                {{-- TAB UNTUK FOTO EDIT --}}
                <div class="tab-pane active" id="editPhotos" role="tabpanel">
                    @if(!empty($transaksi->select_edit_photo))
                        <div class="row g-3">
                            @foreach($selectedPhotos as $photoPath)
                                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                                    <div class="card photo-card selected-edit h-100">
                                        <div class="image-container" style="aspect-ratio: 1 / 1;">
                                            <img src="{{ $photoPath }}"
                                                 class="fixed-image"
                                                 alt="Selected Photo for Editing"
                                                 onclick="openModal('{{ $photoPath) }}')"
                                                 style="cursor: pointer;">
                                        </div>
                                        <div class="card-body p-2 text-center">
                                            <p class="text-muted small mb-0 text-truncate">{{ basename($photoPath) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-soft-info text-center">
                            No photos have been selected for editing.
                        </div>
                    @endif
                </div>

                {{-- TAB UNTUK FOTO CETAK --}}
                <div class="tab-pane" id="printPhotos" role="tabpanel">
                    @if(!empty($transaksi->select_print_photo))
                        <div class="row g-3">
                            @foreach($selectedPrints as $photoPath)
                                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                                    <div class="card photo-card selected-print h-100">
                                        <div class="image-container" style="aspect-ratio: 1 / 1;">
                                            <img src="{{ $photoPath) }}"
                                                 class="fixed-image"
                                                 alt="Selected Photo for Printing"
                                                 onclick="openModal('{{ $photoPath }}')"
                                                 style="cursor: pointer;">
                                        </div>
                                        <div class="card-body p-2 text-center">
                                            <p class="text-muted small mb-0 text-truncate">{{ basename($photoPath) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-soft-info text-center">
                            No photos have been selected for printing.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('admin.transaction.photo-modal-readonly')
@endsection

@section('script')
    <script>
        function openModal(photoUrl) {
            const modalEl = document.getElementById('photoModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                document.getElementById('modalPhoto').src = photoUrl;
                modal.show();
            }
        }
    </script>
@endsection