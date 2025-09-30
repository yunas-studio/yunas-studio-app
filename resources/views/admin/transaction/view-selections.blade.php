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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Gallery for #{{ $transaksi->receipt_code }}</h2>
                    <p class="text-muted mb-0">Customer: {{ $transaksi->customer_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    @if($photoCount > 0)
                    <a href="{{ route('transaksi.downloadFolder', ['transaksi' => $transaksi, 'status' => $currentFilter]) }}" class="btn btn-success">
                        <i class="bx bx-download me-1"></i> Download This Folder
                    </a>
                    @endif
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Transactions
                    </a>
                </div>
            </div>

            {{-- Filter Buttons --}}
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="{{ route('transaksi.view-selections', ['transaksi' => $transaksi->transaction_id, 'filter' => 'raw']) }}" class="btn btn-outline-primary {{ $currentFilter === 'RAW' ? 'active' : '' }}">RAW</a>
                    <a href="{{ route('transaksi.view-selections', ['transaksi' => $transaksi->transaction_id, 'filter' => 'pilih_edit']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Pilih Edit' ? 'active' : '' }}">Pilih Edit</a>
                    <a href="{{ route('transaksi.view-selections', ['transaksi' => $transaksi->transaction_id, 'filter' => 'result']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Result' ? 'active' : '' }}">Editing Result</a>
                    <a href="{{ route('transaksi.view-selections', ['transaksi' => $transaksi->transaction_id, 'filter' => 'pilih_cetak']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Pilih Cetak' ? 'active' : '' }}">Pilih Cetak</a>
                </div>
            </div>
            
            @if($currentFilter === 'RAW')
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    This tab shows all original photos. Photos selected by the user are highlighted with badges.
                </div>
            @endif

            @if(count($photoUrls) > 0)
                <div class="row g-3">
                    @foreach ($photoUrls as $index => $url)
                        @php
                            $isSelectedForEdit = in_array($url, $selectedUrls);
                            $printSize = $selectedForPrint[$url] ?? null;
                            
                            $cardClass = '';
                            if ($isSelectedForEdit && $printSize) {
                                $cardClass = 'selected-both';
                            } elseif ($isSelectedForEdit) {
                                $cardClass = 'selected-edit';
                            } elseif ($printSize) {
                                $cardClass = 'selected-print';
                            }
                        @endphp
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="card photo-card h-100 {{ $cardClass }}">
                                <div class="image-container ratio ratio-4x3">
                                    <img src="{{ $url }}" class="card-img-top fixed-image" alt="Photo {{ $index + 1 }}" loading="lazy">
                                </div>
                                <div class="card-body d-flex flex-column p-2">
                                    <h6 class="card-title small">Photo #{{ $loop->iteration }}</h6>
                                    
                                    {{-- Display selection badges only on the RAW tab --}}
                                    @if($currentFilter === 'RAW')
                                        <div style="min-height: 40px;">
                                            @if($isSelectedForEdit)
                                                <span class="badge bg-primary mb-1">For Editing</span>
                                            @endif
                                            @if($printSize)
                                                <span class="badge bg-success mb-1">Print: {{ $printSize }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="mt-auto">
                                        <div class="btn-group w-100">
                                             <button type="button" class="btn btn-sm btn-outline-info" onclick="openModal('{{ $url }}')" title="Preview"><i class="bx bx-fullscreen"></i></button>
                                             <a href="{{ $url }}" class="btn btn-sm btn-outline-secondary" download title="Download"><i class="bx bx-download"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center py-5">
                    <h4><i class="bx bx-image-alt bx-lg mb-3 d-block"></i>No Photos Found</h4>
                    <p class="mb-0">There are no photos in the '{{ $currentFilter }}' folder for this transaction.</p>
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