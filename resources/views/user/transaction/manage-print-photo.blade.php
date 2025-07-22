@extends('layouts.master')
@section('title', $pageTitle ?? 'Photo Selection')

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Photo Selection',
        'pagetitle' => 'Gallery',
        'breadcrumbs' => [['text' => 'Gallery', 'url' => '#'], ['text' => $pageTitle, 'url' => '']]
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
                    <h2 class="mb-0">{{ $pageTitle }} for #{{ $transaksi->receipt_code }}</h2>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Selection
                        </button>
                    </div>
                </div>

                @if($photoCount > 0)
                    <div class="row g-4">
                        @foreach ($photoUrls as $index => $url)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card photo-card h-100 mx-auto">
                                    <div class="image-container ratio ratio-4x3 position-relative">
                                        <img src="{{ $url }}" class="fixed-image" alt="Photo {{ $index + 1 }}" loading="lazy">
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <input type="checkbox" name="photo_urls[]" value="{{ $url }}" id="photo-{{ $index }}" class="form-check-input photo-checkbox"
                                                   @if(in_array($url, $selectedPhotos)) checked @endif>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning text-center py-5">
                        <h4>No Photos Available</h4>
                    </div>
                @endif
            </form>
        </div>
    </div>
    @include('partials.success-modal')
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success_message'))
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            @endif
        });
    </script>
@endsection