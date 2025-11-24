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
                            // --- BUG FIX: LOGIKA PENCOCOKAN FILE YANG LEBIH KUAT ---
                            $filename = basename($url);
                            
                            // 1. Cek status Edit
                            $isSelectedForEdit = false;
                            foreach($selectedUrls as $sUrl) {
                                if(basename($sUrl) === $filename) {
                                    $isSelectedForEdit = true;
                                    break;
                                }
                            }

                            // 2. Cek status Print
                            $printSize = null;
                            foreach($selectedForPrint as $pUrl => $size) {
                                if(basename($pUrl) === $filename) {
                                    $printSize = $size;
                                    break;
                                }
                            }
                            
                            // 3. Tentukan Class Card
                            $cardClass = '';
                            if ($isSelectedForEdit && $printSize) {
                                $cardClass = 'selected-both';
                            } elseif ($isSelectedForEdit) {
                                $cardClass = 'selected-edit';
                            } elseif ($printSize) {
                                $cardClass = 'selected-print';
                            }

                            // 4. Siapkan URL Bersih (Tanpa /Thumbnails/) untuk Preview/Download
                            $cleanUrl = str_replace(['/Thumbnails/', '/Thumbnails'], ['/', ''], $url);
                        @endphp
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="card photo-card h-100 {{ $cardClass }}">
                                <div class="image-container ratio ratio-4x3">
                                    {{-- Tampilkan Thumbnail untuk load cepat --}}
                                    <img src="{{ $url }}" class="card-img-top fixed-image" alt="Photo {{ $index + 1 }}" loading="lazy">
                                </div>
                                <div class="card-body d-flex flex-column p-2">
                                    <h6 class="card-title small text-truncate" title="{{ $filename }}">{{ $filename }}</h6>
                                    
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
                                             <button type="button" class="btn btn-sm btn-outline-info" onclick="openModal('{{ $cleanUrl }}')" title="Preview"><i class="bx bx-fullscreen"></i></button>
                                             <a href="{{ $cleanUrl }}" class="btn btn-sm btn-outline-secondary" download title="Download"><i class="bx bx-download"></i></a>
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
            const modalEl = document.getElementById('photoModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                document.getElementById('modalPhoto').src = photoUrl;
                modal.show();
            }
        }
    </script>
@endsection