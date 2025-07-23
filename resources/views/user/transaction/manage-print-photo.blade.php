@extends('layouts.master')
@section('title', $pageTitle ?? 'Photo Selection')

@section('css')
    <style>
        .photo-card {
            border: 2px solid #e9ecef;
            transition: all 0.2s ease;
        }
        .photo-card.selected {
            border-color: #2ab57d;
            box-shadow: 0 4px 15px rgba(42, 181, 125, 0.3);
        }
        .image-container { overflow: hidden; }
        .fixed-image { object-fit: cover; }
        .print-assignment {
            font-size: 0.8rem;
            font-weight: bold;
            color: #2ab57d;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Photo Selection',
        'pagetitle' => 'Gallery',
        'breadcrumbs' => [['text' => 'Gallery', 'url' => '#'], ['text' => $pageTitle, 'url' => '']]
    ])
    @endcomponent

    <div class="container-fluid bg-white py-4">
        <div class="container">
            <form id="photoSelectionForm" method="POST" action="{{ $formAction }}">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">{{ $pageTitle }} for #{{ $transaksi->receipt_code }}</h2>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Save Print Selection
                    </button>
                </div>

                @if(!empty($printAllowances))
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Your Print Allowance</h5>
                            <p class="card-subtitle text-muted mb-3">Select a photo and assign it to one of your available prints.</p>
                            <div id="allowance-tracker" class="d-flex flex-wrap gap-3">
                                @foreach($printAllowances as $size => $quantity)
                                    <div class="border rounded p-2 text-center">
                                        <h6 class="mb-0">{{ $size }}</h6>
                                        <span class="fs-5 fw-bold" data-size="{{ $size }}">{{ $quantity }}</span>
                                        <small class="d-block text-muted">remaining</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($photoCount > 0)
                    <div class="row g-4" id="photo-grid">
                        @foreach ($photoUrls as $index => $url)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card photo-card h-100 mx-auto" data-url="{{ $url }}">
                                    <div class="image-container ratio ratio-4x3">
                                        <img src="{{ $url }}" class="fixed-image card-img-top" alt="Photo {{ $index + 1 }}" loading="lazy">
                                    </div>
                                    <div class="card-body text-center d-flex flex-column">
                                        <h5 class="card-title fs-6 mb-2">Photo #{{ $index + 1 }}</h5>
                                        <div class="print-assignment mb-2" style="display: none;"></div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-auto assign-btn">
                                            Assign to Print
                                        </button>
                                        <input type="hidden" name="selected_photos[{{ $url }}]" class="selection-input">
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

    <div class="modal fade" id="assignPrintModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Assign Photo to Print</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Select a print size for this photo:</p>
                    <select id="modalPrintSizeSelect" class="form-select">
                        <option value="">-- Don't Print --</option>
                        @foreach($printAllowances as $size => $quantity)
                            <option value="{{ $size }}">{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAssignmentBtn">Save Assignment</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.success-modal')
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success_message'))
                new bootstrap.Modal(document.getElementById('successModal')).show();
            @endif

            const assignModal = new bootstrap.Modal(document.getElementById('assignPrintModal'));
            const modalSelect = document.getElementById('modalPrintSizeSelect');
            const saveAssignmentBtn = document.getElementById('saveAssignmentBtn');
            const photoGrid = document.getElementById('photo-grid');
            let activeCard = null;

            const allowances = @json($printAllowances);
            const initialSelections = @json($selectedForPrint->mapWithKeys(function($item) { return [$item->file_url => $item->print_size]; }));
            let selections = { ...initialSelections };

            function updateAllowanceDisplay() {
                const counts = {};
                // Count current selections
                Object.values(selections).forEach(size => {
                    if (size) counts[size] = (counts[size] || 0) + 1;
                });

                // Update display and disable options if limit is reached
                document.querySelectorAll('#allowance-tracker [data-size]').forEach(span => {
                    const size = span.dataset.size;
                    const total = allowances[size] || 0;
                    const used = counts[size] || 0;
                    span.textContent = total - used;

                    const option = modalSelect.querySelector(`option[value="${size}"]`);
                    if (option) {
                        option.disabled = (total - used <= 0);
                    }
                });
            }

            function updateCardDisplay() {
                document.querySelectorAll('.photo-card').forEach(card => {
                    const url = card.dataset.url;
                    const assignmentDiv = card.querySelector('.print-assignment');
                    const assignBtn = card.querySelector('.assign-btn');
                    const input = card.querySelector('.selection-input');

                    if (selections[url]) {
                        card.classList.add('selected');
                        assignmentDiv.textContent = `Assigned: ${selections[url]}`;
                        assignmentDiv.style.display = 'block';
                        assignBtn.textContent = 'Change Assignment';
                        input.value = selections[url];
                    } else {
                        card.classList.remove('selected');
                        assignmentDiv.style.display = 'none';
                        assignBtn.textContent = 'Assign to Print';
                        input.value = '';
                    }
                });
            }

            photoGrid.addEventListener('click', function(e) {
                if (e.target.classList.contains('assign-btn')) {
                    activeCard = e.target.closest('.photo-card');
                    const url = activeCard.dataset.url;
                    
                    // Reset and update select options before showing
                    updateAllowanceDisplay();
                    
                    // If the current photo is already selected, its own selection shouldn't count against the limit
                    const currentSelection = selections[url];
                    if(currentSelection){
                        const option = modalSelect.querySelector(`option[value="${currentSelection}"]`);
                        if(option) option.disabled = false;
                    }
                    
                    modalSelect.value = currentSelection || '';
                    assignModal.show();
                }
            });

            saveAssignmentBtn.addEventListener('click', function() {
                const url = activeCard.dataset.url;
                const newSize = modalSelect.value;
                selections[url] = newSize;

                updateCardDisplay();
                updateAllowanceDisplay();
                assignModal.hide();
            });

            // Initial setup
            updateCardDisplay();
            updateAllowanceDisplay();
        });
    </script>
@endsection