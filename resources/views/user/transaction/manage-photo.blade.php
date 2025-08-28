@extends('layouts.master')
@section('title', 'Select Photos for Editing & Printing')

@section('css')
    <style>
        /* Copied from admin's view-selections.blade.php for consistency */
        .photo-card {
            border: 2px solid transparent;
            border-radius: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .photo-card.has-selection {
            border-color: #556ee6;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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
        .selection-badge { font-size: 0.75rem; }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Photo Selection', 'pagetitle' => 'Gallery'])
    @endcomponent

    <div class="container-fluid">
        <form id="photoSelectionForm" method="POST" action="{{ $formAction }}">
            @csrf
            <div class="row" style="position: sticky; top: 70px; z-index: 1020;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <h4 class="mb-1">Photo Selection for #{{ $transaksi->receipt_code }}</h4>
                                    <p class="text-muted mb-0">Select photos for editing and assign your included prints.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary btn-lg"><i class="bx bx-arrow-back me-1"></i> Back</a>
                                    <a href="{{ route('transaksi.downloadAll', $transaksi) }}" class="btn btn-success btn-lg"><i class="bx bx-download me-1"></i> Download All</a>
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="bx bx-save me-1"></i> Submit All Selections</button>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-md-6 border-end">
                                    <h5 class="mb-1">For Editing</h5>
                                    <p class="mb-0"><span id="edit-count">0</span> / {{ $photoLimit }} selected</p>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-1">For Printing</h5>
                                    <div id="print-allowance-tracker" class="d-flex justify-content-center flex-wrap gap-3">
                                        @if(empty($printAllowances))
                                            <p class="mb-0 text-muted">No prints included in this packet.</p>
                                        @else
                                            @foreach($printAllowances as $size => $quantity)
                                                <div data-size="{{ $size }}">
                                                    <span>0</span>/{{ $quantity }} <span class="text-muted">{{ $size }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($photoCount > 0)
                <div class="row g-3">
                    @foreach ($photoUrls as $index => $url)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="card photo-card h-100" data-url="{{ $url }}">
                                <div class="image-container ratio ratio-4x3">
                                    <img src="{{ $url }}" class="card-img-top fixed-image" alt="Photo {{ $index + 1 }}" loading="lazy">
                                </div>
                                <div class="card-body d-flex flex-column p-2">
                                    <h6 class="card-title small">Photo #{{ $loop->iteration }}</h6>
                                    <div class="selection-badges my-2" style="min-height: 20px;"></div>
                                    <div class="mt-auto">
                                        <button type="button" class="btn btn-primary btn-sm w-100 mb-2 instant-select-btn">Select for Edit</button>
                                        <div class="btn-group w-100">
                                             <button type="button" class="btn btn-sm btn-outline-info preview-btn" title="Preview"><i class="bx bx-fullscreen"></i></button>
                                             <button type="button" class="btn btn-sm btn-outline-secondary manage-selection-btn" title="Manage Print"><i class="bx bx-printer"></i></button>
                                             <a href="{{ $url }}" class="btn btn-sm btn-outline-success" download title="Download"><i class="bx bx-download"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center py-5"><h4>No Photos Found</h4></div>
            @endif
        </form>
    </div>

    <!-- Selection Modal -->
    <div class="modal fade" id="selectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Manage Photo Selection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <img id="modalPhotoPreview" src="" class="img-fluid rounded mb-4" alt="Photo Preview">
                    <input type="hidden" id="currentPhotoUrl">
                    
                    <div class="form-check form-switch form-switch-lg mb-3">
                        <input class="form-check-input" type="checkbox" id="modalSelectForEdit">
                        <label class="form-check-label" for="modalSelectForEdit">Select for Editing</label>
                    </div>

                    <div class="mb-3">
                        <label for="modalAssignToPrint" class="form-label">Assign to Print</label>
                        <select id="modalAssignToPrint" class="form-select">
                            <option value="">-- Not for printing --</option>
                            @foreach($printAllowances as $size => $quantity)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto" id="clearSelectionBtn">Clear Selection</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveSelectionBtn">Apply</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview-only Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photo Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="previewModalPhoto" src="" class="img-fluid" style="max-height: 80vh; object-fit: contain;" alt="Photo Preview">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.success-modal')
@endsection

@section('script-bottom')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- CONFIGURATION ---
    const config = {
        photoLimit: {{ $photoLimit }},
        printAllowances: @json($printAllowances),
        initialEditSelections: @json($selectedForEdit),
        initialPrintSelections: @json($selectedForPrint),
    };

    // --- STATE MANAGEMENT ---
    let selections = {};
    let activeModalUrl = null;

    // --- DOM ELEMENTS ---
    const selectionModal = new bootstrap.Modal(document.getElementById('selectionModal'));
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const elements = {
        form: document.getElementById('photoSelectionForm'),
        editCount: document.getElementById('edit-count'),
        printTracker: document.getElementById('print-allowance-tracker'),
        modal: {
            preview: document.getElementById('modalPhotoPreview'),
            currentUrl: document.getElementById('currentPhotoUrl'),
            editCheckbox: document.getElementById('modalSelectForEdit'),
            printSelect: document.getElementById('modalAssignToPrint'),
            saveBtn: document.getElementById('saveSelectionBtn'),
            clearBtn: document.getElementById('clearSelectionBtn'),
        },
        previewModalPhoto: document.getElementById('previewModalPhoto'),
    };

    // --- FUNCTIONS ---
    const updateDisplays = () => {
        const editCount = Object.values(selections).filter(s => s.edit).length;
        elements.editCount.textContent = editCount;

        const printCounts = {};
        Object.values(selections).filter(s => s.print).forEach(s => {
            printCounts[s.print] = (printCounts[s.print] || 0) + 1;
        });

        elements.printTracker.querySelectorAll('[data-size]').forEach(el => {
            const size = el.dataset.size;
            el.querySelector('span').textContent = printCounts[size] || 0;
        });

        document.querySelectorAll('.photo-card').forEach(card => {
            const url = card.dataset.url;
            const selection = selections[url];
            const badgeContainer = card.querySelector('.selection-badges');
            badgeContainer.innerHTML = '';

            if (selection && (selection.edit || selection.print)) {
                card.classList.add('has-selection');
                if (selection.edit) {
                    badgeContainer.innerHTML += `<span class="badge bg-primary selection-badge">For Editing</span> `;
                }
                if (selection.print) {
                    badgeContainer.innerHTML += `<span class="badge bg-success selection-badge">Print: ${selection.print}</span>`;
                }
            } else {
                card.classList.remove('has-selection');
            }
        });
    };

    const openSelectionModal = (url) => {
        activeModalUrl = url;
        elements.modal.preview.src = url;
        elements.modal.currentUrl.value = url;

        const currentSelection = selections[url] || { edit: false, print: null };
        elements.modal.editCheckbox.checked = currentSelection.edit;
        elements.modal.printSelect.value = currentSelection.print || "";
        
        const editCount = Object.values(selections).filter(s => s.edit).length;
        elements.modal.editCheckbox.disabled = (editCount >= config.photoLimit && !currentSelection.edit);

        const printCounts = {};
        Object.values(selections).filter(s => s.print).forEach(s => {
            printCounts[s.print] = (printCounts[s.print] || 0) + 1;
        });
        
        Array.from(elements.modal.printSelect.options).forEach(option => {
            if (option.value) {
                const limit = config.printAllowances[option.value] || 0;
                const count = printCounts[option.value] || 0;
                option.disabled = (count >= limit && currentSelection.print !== option.value);
            }
        });

        selectionModal.show();
    };
    
    const openPreviewModal = (url) => {
        elements.previewModalPhoto.src = url;
        previewModal.show();
    };

    const saveModalSelection = () => {
        const url = activeModalUrl;
        if (!url) return;

        selections[url] = {
            edit: elements.modal.editCheckbox.checked,
            print: elements.modal.printSelect.value || null
        };

        if (!selections[url].edit && !selections[url].print) {
            delete selections[url];
        }

        updateHiddenInputs();
        updateDisplays();
        selectionModal.hide();
    };
    
    const clearModalSelection = () => {
        const url = activeModalUrl;
        if (url && selections[url]) {
            delete selections[url];
            updateHiddenInputs();
            updateDisplays();
        }
        selectionModal.hide();
    };

    const updateHiddenInputs = () => {
        elements.form.querySelectorAll('.selection-input-field').forEach(input => input.remove());
        
        Object.entries(selections).forEach(([url, selection]) => {
            if (selection.edit) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.className = 'selection-input-field';
                input.name = `selections[${url}][edit]`;
                input.value = '1';
                elements.form.appendChild(input);
            }
            if (selection.print) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.className = 'selection-input-field';
                input.name = `selections[${url}][print]`;
                input.value = selection.print;
                elements.form.appendChild(input);
            }
        });
    };
    
    // --- INITIALIZATION ---
    config.initialEditSelections.forEach(url => {
        selections[url] = { ...selections[url], edit: true };
    });
    Object.entries(config.initialPrintSelections).forEach(([url, size]) => {
        selections[url] = { ...selections[url], print: size };
    });
    
    updateHiddenInputs();
    updateDisplays();

    // --- EVENT LISTENERS ---
    document.querySelectorAll('.manage-selection-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const url = e.target.closest('.photo-card').dataset.url;
            openSelectionModal(url);
        });
    });
    
    document.querySelectorAll('.preview-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const url = e.target.closest('.photo-card').dataset.url;
            openPreviewModal(url);
        });
    });

    document.querySelectorAll('.instant-select-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const url = e.target.closest('.photo-card').dataset.url;
            const currentSelection = selections[url] || { edit: false, print: null };
            const editCount = Object.values(selections).filter(s => s.edit).length;

            if (!currentSelection.edit && editCount >= config.photoLimit) {
                alert(`You can only select up to ${config.photoLimit} photos for editing.`);
                return;
            }
            
            currentSelection.edit = !currentSelection.edit;
            selections[url] = currentSelection;
            
            if (!selections[url].edit && !selections[url].print) {
                delete selections[url];
            }

            updateHiddenInputs();
            updateDisplays();
        });
    });
    
    elements.modal.saveBtn.addEventListener('click', saveModalSelection);
    elements.modal.clearBtn.addEventListener('click', clearModalSelection);

    @if (session('success_message'))
        new bootstrap.Modal(document.getElementById('successModal')).show();
    @endif
});
</script>
@endsection
