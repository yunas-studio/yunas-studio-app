@extends('layouts.master')
@section('title', 'View User Photo Selection')

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
        .photo-card.selected {
            border-color: #556ee6; /* Highlight selected photos with a clear border */
        }
        .image-container {
            overflow: hidden;
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
        'title' => 'View Selection',
        'pagetitle' => 'Transactions',
        'breadcrumbs' => [
            ['text' => 'Transactions', 'url' => route('transaksi.index')],
            ['text' => 'View Selection', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="container-fluid bg-white py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Viewing Selection for #{{ $transaksi->receipt_code }}</h2>
                    <p class="text-muted mb-0">Customer: {{ $transaksi->customer_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('transaksi.downloadSelected', $transaksi) }}" class="btn btn-success">
                        <i class="bx bx-download me-1"></i> Download Selected
                    </a>
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Transactions
                    </a>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                The user has selected <strong>{{ count($selectedUrls) }}</strong> photos for editing and <strong>{{ $selectedForPrint->count() }}</strong> photos for printing.
            </div>

            @if(count($photoUrls) > 0)
                <div class="row g-4">
                    @foreach ($photoUrls as $index => $url)
                        @php
                            $isSelectedForEdit = in_array($url, $selectedUrls);
                            $printSize = $selectedForPrint[$url] ?? null;
                        @endphp
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card photo-card h-100 mx-auto {{ ($isSelectedForEdit || $printSize) ? 'selected' : '' }}">
                                <div class="image-container ratio ratio-4x3 position-relative">
                                    <img src="{{ $url }}"
                                         class="fixed-image card-img-top"
                                         alt="Photo {{ $index + 1 }}"
                                         loading="lazy"
                                         onerror="this.closest('.col-12').style.display='none';">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fs-6 mb-1">Photo #{{ $index + 1 }}</h5>
                                    
                                    @if($isSelectedForEdit)
                                        <p class="text-primary fw-bold small mb-2">
                                            <i class="bx bx-check-circle me-1"></i>Selected for Edit
                                        </p>
                                    @endif

                                    @if($printSize)
                                        <p class="text-success fw-bold small mb-2">
                                            <i class="bx bx-printer me-1"></i>Print: {{ $printSize }}
                                        </p>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                onclick="openModal('{{ $url }}')">
                                            <i class="bx bx-fullscreen me-1"></i> Preview
                                        </button>
                                        <small class="text-muted">{{ $loop->iteration }}/{{ $loop->count }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center py-5">
                    <h4>No Photos Found</h4>
                    <p class="mb-0">There are no photos in the RAW directory for this transaction.</p>
                </div>
            @endif
        </div>
    </div>

    @include('admin.transaction.photo-modal-readonly')
@endsection

@section('script')
    <script>
        function openModal(photoUrl) {
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            document.getElementById('modalPhoto').src = photoUrl;
            modal.show();
        }
    </script>
@endsection