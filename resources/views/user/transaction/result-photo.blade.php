@extends('layouts.master')
@section('title', 'Photo Gallery')

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Photo Gallery',
        'pagetitle' => 'Gallery',
        'breadcrumbs' => [
            ['text' => 'Gallery', 'url' => '#'],
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Photos for Transaction #{{ $transaksi->receipt_code }}</h2>
                <div class="badge bg-primary rounded-pill">
                    {{ $photoCount }} Photos
                </div>
            </div>

            @if($photoCount > 0)
                <div class="row g-4">
                    @foreach ($photoUrls as $index => $url)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card photo-card h-100 mx-auto">
                                <div class="image-container ratio ratio-4x3 position-relative">
                                    <img src="{{ $url }}"
                                         class="fixed-image"
                                         alt="Photo {{ $index + 1 }}"
                                         loading="lazy">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fs-6 mb-2">Photo #{{ $index + 1 }}</h5>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                onclick="openModal('{{ $url }}', '{{ $transaksi->receipt_code }}-photo-{{ $index + 1 }}')">
                                            <i class="fas fa-expand me-1"></i> Preview
                                        </button>
                                        <a href="{{ $url }}" download="{{ $transaksi->receipt_code }}-photo-{{ $index + 1 }}.jpg" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center py-5">
                    <i class="fas fa-camera-slash fa-3x mb-3"></i>
                    <h4>No Photos Available</h4>
                    <p class="mb-0">There are no photos for this transaction.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photo Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" class="img-fluid" alt="Preview">
                </div>
                <div class="modal-footer">
                    <a id="downloadBtn" href="#" download class="btn btn-primary">
                        <i class="fas fa-download me-1"></i> Download Photo
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

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

@section('script')
    <script>
        // Modal function with download setup
        function openModal(photoUrl, photoName) {
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            const modalPhoto = document.getElementById('modalPhoto');
            const downloadBtn = document.getElementById('downloadBtn');
            
            modalPhoto.src = photoUrl;
            downloadBtn.href = photoUrl;
            downloadBtn.download = photoName + '.jpg';
            
            modal.show();
        }
    </script>
@endsection