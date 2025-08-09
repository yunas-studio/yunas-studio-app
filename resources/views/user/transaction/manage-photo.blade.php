@extends('layouts.master')
@section('title', 'Select Photos for Editing & Printing')

@section('css')
    <style>
        .summary-box { background-color: #f8f9fa; border: 1px solid #dee2e6; }
        .photo-card { border: 2px solid transparent; transition: all 0.2s ease-in-out; }
        .photo-card.has-selection { border-color: #556ee6; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .photo-card .card-img-top { object-fit: cover; }
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
                                <button type="submit" class="btn btn-primary btn-lg"><i class="bx bx-save me-1"></i> Submit All Selections</button>
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
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card photo-card h-100" data-url="{{ $url }}">
                                <img src="{{ $url }}" class="card-img-top" alt="Photo {{ $index + 1 }}" height="200">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Photo #{{ $loop->iteration }}</h6>
                                    <div class="selection-badges my-2" style="min-height: 22px;"></div>
                                    <button type="button" class="btn btn-outline-primary manage-selection-btn">Manage Selection</button>
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
    let selections = {}; // { "photo_url": { edit: bool, print: "size" | null } }
    let activeModalUrl = null;

    // --- DOM ELEMENTS ---
    const selectionModal = new bootstrap.Modal(document.getElementById('selectionModal'));
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
        }
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
        
        // Disable options if limits are reached
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

    const saveModalSelection = () => {
        const url = activeModalUrl;
        if (!url) return;

        selections[url] = {
            edit: elements.modal.editCheckbox.checked,
            print: elements.modal.printSelect.value || null
        };

        // If selection is now empty, remove it from the object
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
        // Clear previous inputs to avoid stale data
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
    
    elements.modal.saveBtn.addEventListener('click', saveModalSelection);
    elements.modal.clearBtn.addEventListener('click', clearModalSelection);

    @if (session('success_message'))
        new bootstrap.Modal(document.getElementById('successModal')).show();
    @endif
});
</script>
@endsection