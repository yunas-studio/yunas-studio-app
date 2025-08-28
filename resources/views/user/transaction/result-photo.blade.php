@extends('layouts.master')
@section('title', 'Photo Gallery')

@section('css')
    <style>
        .photo-card {
            border: 1px solid rgba(0,0,0,0.125);
            border-radius: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .image-container {
            background-color: #f8f9fa;
            overflow: hidden;
        }

        .fixed-image {
            object-fit: cover;
            object-position: center;
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
        'pagetitle' => 'Gallery',
        'breadcrumbs' => [
            ['text' => 'My Transactions', 'url' => route('transaksi.index')],
            ['text' => 'View Photos', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="container-fluid bg-white py-4">
        <div class="container">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">← Back</a>
                    <h2 class="mb-0 d-none d-md-block">Photos for #{{ $transaksi->receipt_code }}</h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill">{{ $photoCount }} Photos</span>
                    @if($photoCount > 0)
                    <a href="{{ route('transaksi.downloadFolder', ['transaksi' => $transaksi, 'status' => $currentFilter]) }}" class="btn btn-success">
                        <i class="bx bx-download me-1"></i> Download All
                    </a>
                    @endif
                </div>
            </div>

            {{-- Filter Buttons --}}
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'raw']) }}" class="btn btn-outline-primary {{ $currentFilter === 'RAW' ? 'active' : '' }}">RAW</a>
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'pilih_edit']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Pilih Edit' ? 'active' : '' }}">Pilih Edit</a>
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'result']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Result' ? 'active' : '' }}">Editing Result</a>
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'pilih_cetak']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Pilih Cetak' ? 'active' : '' }}">Pilih Cetak</a>
                </div>
            </div>

            {{-- Informational Text --}}
            <div class="alert alert-info">
                @if($currentFilter === 'RAW')
                    <i class="bx bx-camera me-2"></i>This is your current gallery of RAW photos from the photographer.
                @elseif($currentFilter === 'Pilih Edit')
                    <i class="bx bx-edit me-2"></i>This is your current selection of photos for our editor to work on.
                @elseif($currentFilter === 'Result')
                    <i class="bx bx-check-double me-2"></i>These are the final, edited photos. You can now download them.
                @elseif($currentFilter === 'Pilih Cetak')
                    @if(!$printAllowances)
                        <i class="bx bx-info-circle me-2"></i>Your packet does not include any photo prints.
                    @elseif($transaksi->process_status === 'Proses Cetak')
                        <i class="bx bx-time-five me-2"></i>Your photos are currently in the printing process. Please wait for further information.
                    @else
                        <i class="bx bx-printer me-2"></i>This is your current selection of photos for printing.
                    @endif
                @endif
            </div>

            @if($photoCount > 0)
                <div class="row g-4">
                    @foreach ($photoUrls as $index => $url)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card photo-card h-100 mx-auto">
                                <div class="image-container ratio ratio-4x3">
                                    <img src="{{ $url }}" class="card-img-top fixed-image" alt="Photo {{ $index + 1 }}" loading="lazy">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fs-6 mb-2">Photo #{{ $loop->iteration }}</h5>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openModal('{{ $url }}', '{{ $transaksi->receipt_code }}-photo-{{ $index + 1 }}')">
                                            <i class="bx bx-fullscreen me-1"></i> Preview
                                        </button>
                                        <a href="{{ $url }}" download="{{ $transaksi->receipt_code }}-photo-{{ $index + 1 }}.jpg" class="btn btn-sm btn-outline-success">
                                            <i class="bx bx-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center py-5">
                    <h4><i class="bx bx-image-alt bx-lg mb-3 d-block"></i>No Photos Found</h4>
                    <p class="mb-0">There are no photos in the '{{ $currentFilter }}' folder for this transaction yet.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photo Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" class="img-fluid" alt="Preview" style="max-height: 80vh;">
                </div>
                <div class="modal-footer">
                    <a id="downloadBtn" href="#" download class="btn btn-primary"><i class="bx bx-download me-1"></i> Download Photo</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function openModal(photoUrl, photoName) {
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            const modalPhoto = document.getElementById('modalPhoto');
            const downloadBtn = document.getElementById('downloadBtn');

            modalPhoto.src = photoUrl;
            downloadBtn.href = photoUrl;
            downloadBtn.download = photoName + '.jpg';

            modal.show();
        }

        @if(session('error'))
        document.addEventListener('DOMContentLoaded', function () {
            const toastElement = document.getElementById('errorToast');
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
        });
        @endif
    </script>
@endsection
