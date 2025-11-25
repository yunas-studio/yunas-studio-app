@extends('layouts.master')

@section('title')
    Pilih Foto
@endsection

@section('css')
    <!-- Sweet Alert-->
    <link href="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .step-icon {
            width: 50px;
            height: 50px;
            background-color: #eff2f7;
            color: #556ee6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 24px;
            margin: 0 auto 10px;
            transition: all 0.3s;
        }
        .step-item:hover .step-icon {
            background-color: #556ee6;
            color: #fff;
        }
        .external-link-card {
            border: 2px dashed #ced4da;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        .external-link-card:hover {
            border-color: #556ee6;
            background-color: #fff;
        }

        /* Styles for Tag Input */
        .tag-container {
            border: 1px solid #ced4da;
            padding: 5px;
            border-radius: 0.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            min-height: 100px;
            background-color: #fff;
            cursor: text;
            transition: all 0.3s;
        }
        .tag-container:focus-within {
            border-color: #556ee6;
            box-shadow: 0 0 0 0.15rem rgba(85, 110, 230, 0.25);
        }
        .tag-container.limit-reached {
            background-color: #fff5f5;
            border-color: #f46a6a;
            cursor: not-allowed;
        }
        .tag {
            background-color: #eff2f7;
            color: #495057;
            padding: 5px 10px;
            margin: 4px;
            border-radius: 3px;
            font-size: 13px;
            display: flex;
            align-items: center;
            border: 1px solid #e2e5e8;
        }
        .tag i {
            margin-left: 8px;
            cursor: pointer;
            color: #adb5bd;
        }
        .tag i:hover {
            color: #f46a6a;
        }
        .tag-input {
            border: none;
            outline: none;
            padding: 5px;
            flex-grow: 1;
            min-width: 150px;
            font-size: 13px;
            background: transparent;
        }
        
        /* Style untuk input print individual */
        .print-slot-item {
            background-color: #f8f9fa;
            border: 1px solid #eff2f7;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .print-slot-item:focus-within {
            background-color: #fff;
            border-color: #556ee6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .print-slot-item input:invalid {
            border-color: #f46a6a;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Transaksi @endslot
        @slot('title') Pilih Foto @endslot
    @endcomponent

    @php
        // --- LOGIKA PERHITUNGAN KUOTA FOTO (JATAH EDIT) ---
        $finalMaxPhotos = $transaksi->packet->max_photos_for_edit ?? 0;
        $displayMaxPhotos = $finalMaxPhotos > 0 ? $finalMaxPhotos . ' Foto' : 'Unlimited';
        $jsLimitEdit = $finalMaxPhotos > 0 ? $finalMaxPhotos : 9999;
        $isUnlimitedEdit = $finalMaxPhotos == 0;

        // --- LOGIKA BUILD ARRAY SLOT CETAK ---
        $printSlots = [];
        
        // 1. Dari Included Prints (bawaan paket)
        if($transaksi->packet->printOptions->isNotEmpty()) {
            foreach($transaksi->packet->printOptions as $print) {
                for($i = 0; $i < $print->pivot->quantity; $i++) {
                    $printSlots[] = 'Cetak ' . $print->name;
                }
            }
        }

        // 2. Dari Additional/Extra Items (jika mengandung kata Cetak/Print)
        if($transaksi->additionals->isNotEmpty()) {
            foreach($transaksi->additionals as $additional) {
                if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                    for($i = 0; $i < $additional->pivot->quantity; $i++) {
                        $printSlots[] = $additional->name;
                    }
                }
            }
        }

        $totalPrintQuota = count($printSlots);
        $displayPrintQuota = $totalPrintQuota > 0 ? $totalPrintQuota . ' Lembar' : '0 Lembar';

        // Ambil data cetak yang sudah tersimpan
        $savedPrintFiles = $transaksi->select_print_photo ? array_map('trim', explode(',', $transaksi->select_print_photo)) : [];
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Panduan Langkah -->
            <div class="row mb-4">
                <div class="col-md-4 text-center step-item">
                    <div class="step-icon"><i class="mdi mdi-google-drive"></i></div>
                    <h5 class="font-size-14">1. Buka Galeri</h5>
                    <p class="text-muted mb-0">Klik tombol di bawah untuk melihat semua foto Anda di Google Drive/Cloud.</p>
                </div>
                <div class="col-md-4 text-center step-item">
                    <div class="step-icon"><i class="mdi mdi-file-document-edit-outline"></i></div>
                    <h5 class="font-size-14">2. Catat Nama File</h5>
                    <p class="text-muted mb-0">Catat nama file foto yang ingin Anda edit atau cetak (contoh: IMG_9921.jpg).</p>
                </div>
                <div class="col-md-4 text-center step-item">
                    <div class="step-icon"><i class="mdi mdi-check-circle-outline"></i></div>
                    <h5 class="font-size-14">3. Submit Pilihan</h5>
                    <p class="text-muted mb-0">Isi formulir di bawah sesuai instruksi lalu simpan.</p>
                </div>
            </div>

            <!-- DETAIL PAKET -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-dark"><i class="mdi mdi-information-outline me-2"></i> Informasi Detail Paket</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Kolom Kiri: Info Dasar -->
                        <div class="col-md-5 border-end">
                            <h6 class="font-size-14 text-muted mb-3">Ringkasan Pesanan</h6>
                            <table class="table table-borderless table-sm mb-0 font-size-14">
                                <tr>
                                    <th style="width: 120px;" class="text-muted fw-normal">Produk</th>
                                    <td class="fw-bold text-primary">: {{ $transaksi->packet->product->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Paket</th>
                                    <td class="fw-bold">: {{ $transaksi->packet->name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Kode Invoice</th>
                                    <td>: {{ $transaksi->receipt_code }}</td>
                                </tr>
                                <tr><td colspan="2"><hr class="my-2"></td></tr>
                                <tr>
                                    <th class="text-muted fw-normal">Jatah Edit</th>
                                    <td>: 
                                        <span class="badge bg-soft-primary text-primary font-size-13">
                                            <i class="mdi mdi-image-edit"></i> {{ $displayMaxPhotos }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Jatah Cetak</th>
                                    <td>: 
                                        <span class="badge bg-soft-success text-success font-size-13">
                                            <i class="mdi mdi-printer"></i> {{ $displayPrintQuota }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Kolom Kanan: Tabel Rincian Item -->
                        <div class="col-md-7">
                            <h6 class="font-size-14 text-muted mb-2">Rincian Item Paket & Tambahan:</h6>
                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0 font-size-13">
                                    <tbody>
                                        {{-- Included Prints --}}
                                        @if($transaksi->packet->printOptions->isNotEmpty())
                                            <tr><td colspan="2" class="px-3 py-1 fw-bold text-muted small bg-soft-light">Included Prints</td></tr>
                                            @foreach($transaksi->packet->printOptions as $print)
                                                <tr>
                                                    <td class="px-3 py-1 ps-4"><i class="bx bx-printer me-2 text-secondary"></i> Cetak {{ $print->name }}</td>
                                                    <td class="text-center py-1 fw-bold" width="50">x{{ $print->pivot->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        @endif

                                        {{-- Additional Defaults --}}
                                        @if($transaksi->packet->additionalDefaults->isNotEmpty())
                                            <tr><td colspan="2" class="px-3 py-1 fw-bold text-muted small bg-soft-light">Included Items</td></tr>
                                            @foreach($transaksi->packet->additionalDefaults as $default)
                                                <tr>
                                                    <td class="px-3 py-1 ps-4"><i class="bx bx-check-circle me-2 text-secondary"></i> {{ $default->additional->name }}</td>
                                                    <td class="text-center py-1 fw-bold" width="50">x{{ $default->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        @endif

                                        {{-- Paid Additionals --}}
                                        @if($transaksi->additionals->isNotEmpty())
                                            <tr><td colspan="2" class="px-3 py-1 fw-bold text-warning small bg-soft-warning">Extra Add-ons</td></tr>
                                            @foreach($transaksi->additionals as $additional)
                                                <tr>
                                                    <td class="px-3 py-1 ps-4"><i class="bx bx-plus me-2 text-warning"></i> {{ $additional->name }}</td>
                                                    <td class="text-center py-1 fw-bold" width="50">x{{ $additional->pivot->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        
                                        @if($transaksi->packet->printOptions->isEmpty() && $transaksi->packet->additionalDefaults->isEmpty() && $transaksi->additionals->isEmpty())
                                            <tr><td colspan="2" class="text-center text-muted fst-italic py-2">No specific details available for this package.</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="card-title mb-3">Formulir Pemilihan Foto</h4>
                        <p class="card-title-desc text-muted">
                            Silakan masukkan nama file foto sesuai dengan rincian paket di atas.
                        </p>
                    </div>

                    <!-- Area Link Foto -->
                    <div class="external-link-card rounded p-4 mb-4 text-center">
                        <h5 class="font-size-15 mb-3">Akses Galeri Foto Anda</h5>
                        @if($transaksi->url_images)
                            <a href="{{ $transaksi->url_images }}" target="_blank" class="btn btn-primary btn-lg waves-effect waves-light">
                                <i class="mdi mdi-open-in-new me-2"></i> Buka Link Google Drive
                            </a>
                            <div class="mt-2 text-muted small text-break">
                                <i class="mdi mdi-link-variant"></i> {{ $transaksi->url_images }}
                            </div>
                        @else
                            <div class="alert alert-warning d-inline-flex align-items-center" role="alert">
                                <i class="mdi mdi-alert-outline me-2"></i>
                                Link foto belum tersedia. Hubungi admin jika Anda sudah melakukan sesi foto.
                            </div>
                        @endif
                    </div>

                    <form action="{{ route('transaksi.handle-select-for-edit', ['transaksi' => $transaksi->transaction_id]) }}" method="POST" id="photoSelectionForm">
                        @csrf
                        
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-information-outline me-2"></i>
                            <strong>Tips Cepat:</strong> Anda bisa menyalin (copy) nama file dari Google Drive dan tempel di sini.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>

                        <div class="row">
                            <!-- INPUT EDIT PHOTO (Tag Input) -->
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label class="form-label fw-bold font-size-15">
                                        <i class="mdi mdi-image-edit text-primary me-1"></i> Foto untuk Diedit
                                    </label>
                                    <!-- Hidden Input Asli untuk dikirim ke server -->
                                    <input type="hidden" name="select_edit_photo" id="hidden_edit_photo" value="{{ old('select_edit_photo', $transaksi->select_edit_photo) }}">
                                    
                                    <!-- UI Tag Input -->
                                    <div class="tag-container" id="container_edit_photo">
                                        <input type="text" class="tag-input" placeholder="Ketik nama file lalu tekan Enter atau Koma..." id="input_edit_photo">
                                    </div>
                                    <div class="form-text d-flex justify-content-between align-items-center">
                                        <span>Maksimal: <strong>{{ $displayMaxPhotos }}</strong></span>
                                        <!-- COUNTER UPDATE: Format Terisi n / Max -->
                                        <span class="badge bg-soft-primary text-primary font-size-12 p-2">
                                            Terisi: <span id="count_edit_photo" class="fw-bold">0</span> / {{ $finalMaxPhotos > 0 ? $finalMaxPhotos : '∞' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- INPUT PRINT PHOTO (Separate Inputs) -->
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold font-size-15 mb-0">
                                            <i class="mdi mdi-printer text-success me-1"></i> Foto untuk Dicetak
                                        </label>
                                        @if($transaksi->hasPrintableItems())
                                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#printDetailModal" class="text-primary small fw-bold">
                                                <i class="mdi mdi-eye"></i> Lihat Rincian
                                            </a>
                                        @endif
                                    </div>
                                    
                                    @if($transaksi->hasPrintableItems() && count($printSlots) > 0)
                                        <!-- Hidden Input Asli (yang akan diisi via JS) -->
                                        <input type="hidden" name="select_print_photo" id="hidden_print_photo" value="{{ old('select_print_photo', $transaksi->select_print_photo) }}">
                                        
                                        <div id="print-inputs-wrapper">
                                            @foreach($printSlots as $index => $slotName)
                                                <div class="print-slot-item p-2 mb-2">
                                                    <label class="small text-muted mb-1 d-block">
                                                        <span class="badge badge-soft-secondary me-1">#{{ $index + 1 }}</span>
                                                        {{ $slotName }}
                                                    </label>
                                                    <input type="text" 
                                                           class="form-control print-photo-input" 
                                                           placeholder="Tempel nama file untuk {{ $slotName }} disini..." 
                                                           data-index="{{ $index }}"
                                                           value="{{ $savedPrintFiles[$index] ?? '' }}">
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="form-text d-flex justify-content-between align-items-center">
                                            <span>Isi kolom sesuai ukuran yang tertera.</span>
                                            <span class="badge bg-soft-success text-success font-size-12 p-2">
                                                Terisi: <span id="count_print_filled" class="fw-bold">0</span> / {{ count($printSlots) }}
                                            </span>
                                        </div>
                                    @else
                                        <!-- DISABLED STATE -->
                                        <div class="tag-container bg-light border-danger" style="cursor: not-allowed; opacity: 0.7;">
                                            <input type="text" class="tag-input text-muted" value="Paket tidak mengandung additional print" disabled style="cursor: not-allowed; width: 100%;">
                                        </div>
                                        <div class="text-danger mt-1 small fw-bold">
                                            <i class="mdi mdi-block-helper me-1"></i> Paket ini tidak termasuk item cetak foto.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <a href="{{ route('transaksi.index') }}" class="btn btn-light waves-effect">
                                <i class="bx bx-arrow-back me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success waves-effect waves-light px-4">
                                <i class="bx bx-save me-1"></i> Simpan Pilihan Saya
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Rincian Cetak -->
    @if($transaksi->hasPrintableItems())
    <div class="modal fade" id="printDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Kuota Cetak</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tipe Cetak</th>
                                <th class="text-center">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($transaksi->packet->printOptions->isNotEmpty())
                                @foreach($transaksi->packet->printOptions as $print)
                                    <tr>
                                        <td>Cetak {{ $print->name }}</td>
                                        <td class="text-center fw-bold">{{ $print->pivot->quantity }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            @if($transaksi->additionals->isNotEmpty())
                                @foreach($transaksi->additionals as $additional)
                                    @if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false)
                                        <tr>
                                            <td>{{ $additional->name }}</td>
                                            <td class="text-center fw-bold">{{ $additional->pivot->quantity }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                            <tr class="table-active">
                                <td class="fw-bold">Total Kuota</td>
                                <td class="text-center fw-bold">{{ $totalPrintQuota }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@section('script')
<!-- Include SweetAlert -->
<script src="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. LOGIC TAG INPUT (HANYA UNTUK EDIT FOTO) ---
        function initTagInput(containerId, inputId, hiddenId, counterId, maxLimit) {
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);
            const hiddenInput = document.getElementById(hiddenId);
            const counter = document.getElementById(counterId);

            if(!container || !input) return;

            let tags = [];

            // Load initial values
            const initialValue = hiddenInput.value;
            if (initialValue) {
                tags = initialValue.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
                renderTags();
            }

            function renderTags() {
                const existingTags = container.querySelectorAll('.tag');
                existingTags.forEach(tag => tag.remove());

                tags.slice().reverse().forEach(tag => {
                    const tagEl = document.createElement('div');
                    tagEl.classList.add('tag');
                    tagEl.innerHTML = `<span>${tag}</span><i class="mdi mdi-close" data-tag="${tag}"></i>`;
                    container.prepend(tagEl);
                });

                if(counter) {
                    counter.innerText = tags.length;
                    
                    if (maxLimit) {
                        if (tags.length >= maxLimit) {
                            // LIMIT REACHED: Disable input & style container
                            counter.classList.add('text-danger');
                            container.classList.add('limit-reached');
                            
                            input.disabled = true;
                            input.placeholder = "Batas maksimal (" + maxLimit + ") tercapai";
                        } else {
                            // AVAILABLE: Enable input & reset style
                            counter.classList.remove('text-danger');
                            container.classList.remove('limit-reached');
                            
                            input.disabled = false;
                            input.placeholder = "Ketik nama file lalu tekan Enter atau Koma...";
                        }
                    }
                }
                hiddenInput.value = tags.join(',');
            }

            function addTags(text) {
                // Double check limit before processing (in case input wasn't disabled yet)
                if (maxLimit && tags.length >= maxLimit) {
                    Swal.fire('Limit Tercapai', 'Anda sudah mencapai batas maksimal foto untuk diedit.', 'warning');
                    input.value = '';
                    return;
                }

                const newTags = text.split(/[\n\r,]+/).map(t => t.trim()).filter(t => t !== '');
                
                if (newTags.length === 0) return;

                let tagsToAdd = newTags;
                
                // Check if adding these tags exceeds limit
                if (maxLimit) {
                    const remainingSlots = maxLimit - tags.length;
                    if (newTags.length > remainingSlots) {
                        tagsToAdd = newTags.slice(0, remainingSlots);
                        
                        // Alert user that some tags were ignored
                        Swal.fire({
                            title: 'Melebihi Batas',
                            text: `Hanya ${remainingSlots} foto yang ditambahkan. ${newTags.length - remainingSlots} foto lainnya diabaikan karena melebihi kuota paket.`,
                            icon: 'warning',
                            confirmButtonColor: '#556ee6'
                        });
                    }
                }

                tags = [...tags, ...tagsToAdd];
                renderTags();
                input.value = '';
            }

            container.addEventListener('click', function(e) {
                if (e.target.tagName === 'I') {
                    const tagValue = e.target.getAttribute('data-tag');
                    const index = tags.indexOf(tagValue);
                    if (index > -1) {
                        tags.splice(index, 1);
                        renderTags();
                    }
                }
                // Only focus if input is not disabled
                if (e.target === container && !input.disabled) {
                    input.focus();
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addTags(input.value);
                }
                if (e.key === 'Backspace' && input.value === '' && tags.length > 0) {
                    tags.pop();
                    renderTags();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                addTags(pastedData);
            });
        }

        // Init Edit Photo Tag Input
        const editLimit = {{ $jsLimitEdit }};
        initTagInput('container_edit_photo', 'input_edit_photo', 'hidden_edit_photo', 'count_edit_photo', editLimit);


        // --- 2. LOGIC INPUT TEXT TERPISAH (UNTUK PRINT FOTO) ---
        const printInputs = document.querySelectorAll('.print-photo-input');
        const hiddenPrintInput = document.getElementById('hidden_print_photo');
        const printFilledCounter = document.getElementById('count_print_filled');

        function syncPrintInputs() {
            if(!hiddenPrintInput) return;

            let values = [];
            let filledCount = 0;

            printInputs.forEach(input => {
                const val = input.value.trim();
                values.push(val); 
                if(val !== '') filledCount++;
            });
            
            const cleanValues = values.filter(v => v !== '');
            hiddenPrintInput.value = cleanValues.join(',');
            
            if(printFilledCounter) printFilledCounter.innerText = filledCount;
        }

        // Attach Listeners to Print Inputs
        printInputs.forEach(input => {
            input.addEventListener('input', syncPrintInputs);
            input.addEventListener('change', syncPrintInputs);
        });

        // Run once on load to update counter based on pre-filled data
        syncPrintInputs();

        // --- 3. VALIDASI FORM SEBELUM SUBMIT ---
        const form = document.getElementById('photoSelectionForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validasi Foto Edit
            const currentEditCount = parseInt(document.getElementById('count_edit_photo').innerText) || 0;
            const isUnlimitedEdit = {{ $isUnlimitedEdit ? 'true' : 'false' }};
            
            if (!isUnlimitedEdit && currentEditCount < editLimit) {
                Swal.fire({
                    title: 'Belum Selesai',
                    text: `Kuota foto edit belum terpenuhi. Anda baru memilih ${currentEditCount} dari ${editLimit} foto.`,
                    icon: 'warning',
                    confirmButtonColor: '#556ee6'
                });
                return;
            }

            // Validasi Foto Print
            if (printInputs.length > 0) {
                let printFilled = 0;
                printInputs.forEach(i => {
                    if(i.value.trim() !== '') printFilled++;
                });

                if (printFilled < printInputs.length) {
                    Swal.fire({
                        title: 'Belum Selesai',
                        text: `Kuota foto cetak belum terpenuhi. Mohon lengkapi semua kolom isian cetak (${printFilled}/${printInputs.length}).`,
                        icon: 'warning',
                        confirmButtonColor: '#556ee6'
                    });
                    return;
                }
            }

            // Jika semua validasi lolos, submit form
            this.submit();
        });
    });
</script>
@endsection