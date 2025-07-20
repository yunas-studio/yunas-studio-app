@extends('layouts.master')
@section('title', 'Photo Gallery Selection')

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Photo Selection',
        'pagetitle' => 'Gallery',
        'breadcrumbs' => [
            ['text' => 'Gallery', 'url' => '#'],
            ['text' => 'Select Photos', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="container-fluid bg-white py-4">
        <div class="container">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form id="photoSelectionForm" method="POST" action="{{ $formAction }}">
                @csrf

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Select Photos for Transaction #{{ $transaksi->receipt_code }}</h2>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" id="deselectAllBtn">
                            <i class="fas fa-times-circle me-1"></i> Deselect All
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Selection (Max: {{ $totalImage }})
                        </button>
                    </div>
                </div>

                @if($photoCount > 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Found {{ $photoCount }} photos. Select up to {{ $totalImage }} photos to feature.
                    </div>

                    <div class="row g-4">
                        @foreach ($photoUrls as $index => $url)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card photo-card h-100 mx-auto">
                                    <div class="image-container ratio ratio-4x3 position-relative">
                                        <img src="{{ $url }}"
                                             class="fixed-image"
                                             alt="Photo {{ $index + 1 }}"
                                             loading="lazy">

                                        <div class="position-absolute top-0 end-0 p-2">
                                            <input type="checkbox"
                                                   name="photo_urls[]"
                                                   value="{{ $url }}"
                                                   id="photo-{{ $index }}"
                                                   class="form-check-input photo-checkbox"
                                                   @if(in_array($url, $selectedPhotos)) checked @endif
                                                   data-max-selection="{{ $totalImage }}">
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title fs-6 mb-2">Photo #{{ $index + 1 }}</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    onclick="openModal('{{ $url }}')">
                                                <i class="fas fa-expand me-1"></i> Preview
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
                        <i class="fas fa-camera-slash fa-3x mb-3"></i>
                        <h4>No Photos Available</h4>
                        <p class="mb-0">We couldn't find any photos for this transaction.</p>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @include('user.transaction.photo-modal')
@endsection

@section('css')
    /*<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">*/
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

        .photo-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
            box-shadow: 0 0 0 2px white;
        }

        .photo-checkbox:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maxSelection = {{ $totalImage }};
            const checkboxes = document.querySelectorAll('.photo-checkbox');
            const form = document.getElementById('photoSelectionForm');

            // Initialize with current selection count
            updateSelectionCount();

            // Deselect all functionality
            document.getElementById('deselectAllBtn').addEventListener('click', function() {
                checkboxes.forEach(checkbox => checkbox.checked = false);
                updateSelectionCount();
            });

            // Checkbox change handler
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const selectedCount = document.querySelectorAll('.photo-checkbox:checked').length;

                    if (selectedCount > maxSelection) {
                        this.checked = false;
                        showAlert(`You can only select up to ${maxSelection} photos.`);
                    }

                    updateSelectionCount();
                });
            });

            // Form submission handler
            if (form) {
                form.addEventListener('submit', function(e) {
                    const selectedCount = document.querySelectorAll('.photo-checkbox:checked').length;

                    if (selectedCount === 0) {
                        e.preventDefault();
                        showAlert('Please select at least one photo before submitting.');
                    } else if (selectedCount > maxSelection) {
                        e.preventDefault();
                        showAlert(`You can only select up to ${maxSelection} photos.`);
                    }
                });
            }

            function updateSelectionCount() {
                const selectedCount = document.querySelectorAll('.photo-checkbox:checked').length;
                const submitBtn = form.querySelector('button[type="submit"]');

                if (submitBtn) {
                    submitBtn.innerHTML = `
                        <i class="fas fa-save me-1"></i>
                        Save Selection (${selectedCount}/${maxSelection})
                    `;
                }
            }

            function showAlert(message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3';
                alertDiv.style.zIndex = '1100';
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                document.body.appendChild(alertDiv);

                setTimeout(() => {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }, 3000);
            }
        });

        // Modal functions (would be in a separate JS file in production)
        function openModal(photoUrl) {
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            document.getElementById('modalPhoto').src = photoUrl;
            document.getElementById('currentPhotoUrl').value = photoUrl;
            modal.show();
        }

        function selectCurrentPhoto() {
            const photoUrl = document.getElementById('currentPhotoUrl').value;
            const checkbox = Array.from(document.querySelectorAll('.photo-checkbox'))
                .find(el => el.value === photoUrl);

            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
            modal.hide();
        }
    </script>
@endsection
