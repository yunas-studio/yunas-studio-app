@extends('layouts.master')

@section('title')
    View Selections
@endsection

@section('css')
    <style>
        .selection-card {
            transition: all 0.3s;
            border: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .selection-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .file-list-container {
            max-height: 450px;
            overflow-y: auto;
        }
        .file-item {
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .file-item:hover {
            background-color: #f8f9fa;
            border-left-color: #556ee6;
        }
        .file-name {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
            color: #2a3042;
        }
        .file-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #eff2f7;
            border-radius: 8px;
            color: #556ee6;
            font-size: 18px;
        }
        .card-header-clean {
            background-color: transparent;
            border-bottom: 1px solid #eff2f7;
            padding: 1.25rem;
        }
        /* Custom Scrollbar */
        .file-list-container::-webkit-scrollbar {
            width: 6px;
        }
        .file-list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .file-list-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Transaction @endslot
        @slot('title') View User Selections @endslot
    @endcomponent

    @php
        // Mapping Slot Cetak
        $printSlots = [];
        if($transaksi->packet && $transaksi->packet->printOptions) {
            foreach($transaksi->packet->printOptions as $print) {
                for($i = 0; $i < $print->pivot->quantity; $i++) {
                    $printSlots[] = 'Cetak ' . $print->name;
                }
            }
        }
        if($transaksi->additionals) {
            foreach($transaksi->additionals as $additional) {
                if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                    for($i = 0; $i < $additional->pivot->quantity; $i++) {
                        $printSlots[] = $additional->name;
                    }
                }
            }
        }
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    
                    {{-- Header Area --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 border-bottom pb-4">
                        <div>
                            <h4 class="text-dark mb-1">Detail Pilihan Foto</h4>
                            <p class="text-muted mb-0">
                                <i class="mdi mdi-receipt me-1"></i> {{ $transaksi->receipt_code }} &nbsp;|&nbsp; 
                                <i class="mdi mdi-account me-1"></i> {{ $transaksi->customer_name }}
                            </p>
                        </div>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            @if($urlImages)
                                <a href="{{ $urlImages }}" target="_blank" class="btn btn-light text-primary waves-effect border">
                                    <i class="mdi mdi-google-drive me-1"></i> Buka Link Drive
                                </a>
                            @endif
                            
                            {{-- Tombol Proses Edit (Update Status Value) --}}
                            @if(in_array($transaksi->process_status, ['Pelanggan Pilih Foto', 'Siap Edit dan Cetak']))
                                <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="field" value="process_status">
                                    {{-- UPDATE VALUE STATUS BARU --}}
                                    <input type="hidden" name="value" value="Proses Edit dan Cetak">
                                    <input type="hidden" name="redirect_to" value="index">
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">
                                        <i class="mdi mdi-play-circle-outline me-1"></i> Mulai Proses Edit & Cetak
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        {{-- KOLOM 1: FOTO EDIT --}}
                        <div class="col-md-6 mb-4">
                            <div class="card selection-card h-100">
                                <div class="card-header-clean d-flex justify-content-between align-items-center">
                                    <h5 class="font-size-15 mb-0 text-primary fw-bold">
                                        <i class="bx bx-edit me-2"></i> Foto untuk Diedit
                                    </h5>
                                    <span class="badge bg-soft-primary text-primary pill font-size-12">{{ count($selectedPhotos) }} File</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="file-list-container">
                                        @if(count($selectedPhotos) > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($selectedPhotos as $index => $photo)
                                                    <div class="list-group-item file-item py-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="file-icon me-3">
                                                                <i class="mdi mdi-image-outline"></i>
                                                            </div>
                                                            <div class="flex-grow-1 overflow-hidden">
                                                                <h6 class="file-name mb-1 text-truncate" title="{{ $photo }}">{{ $photo }}</h6>
                                                                <small class="text-muted">Item #{{ $index + 1 }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-5">
                                                <div class="avatar-md mx-auto mb-3">
                                                    <span class="avatar-title rounded-circle bg-light text-muted font-size-24">
                                                        <i class="mdi mdi-image-off"></i>
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-0">Belum ada foto yang dipilih untuk diedit.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM 2: FOTO CETAK --}}
                        <div class="col-md-6 mb-4">
                            <div class="card selection-card h-100">
                                <div class="card-header-clean d-flex justify-content-between align-items-center">
                                    <h5 class="font-size-15 mb-0 text-success fw-bold">
                                        <i class="bx bx-printer me-2"></i> Foto untuk Dicetak
                                    </h5>
                                    <span class="badge bg-soft-success text-success pill font-size-12">{{ count($selectedPrints) }} File</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="file-list-container">
                                        @if(count($selectedPrints) > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($selectedPrints as $index => $photo)
                                                    @php
                                                        $slotName = $printSlots[$index] ?? 'Extra Print';
                                                    @endphp
                                                    <div class="list-group-item file-item py-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="file-icon me-3 bg-soft-success text-success">
                                                                <i class="mdi mdi-printer"></i>
                                                            </div>
                                                            <div class="flex-grow-1 overflow-hidden">
                                                                <h6 class="file-name mb-1 text-truncate" title="{{ $photo }}">{{ $photo }}</h6>
                                                                <span class="badge badge-soft-secondary font-size-11">{{ $slotName }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-5">
                                                <div class="avatar-md mx-auto mb-3">
                                                    <span class="avatar-title rounded-circle bg-light text-muted font-size-24">
                                                        <i class="mdi mdi-printer-off"></i>
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-0">Tidak ada foto yang dipilih untuk dicetak.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start mt-2">
                        <a href="{{ route('transaksi.index') }}" class="btn btn-link text-secondary text-decoration-none ps-0">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar Transaksi
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Menggunakan Toastr atau alert sederhana
                alert('Link berhasil disalin!'); 
            }, function(err) {
                console.error('Gagal menyalin teks: ', err);
            });
        }
    </script>
@endsection