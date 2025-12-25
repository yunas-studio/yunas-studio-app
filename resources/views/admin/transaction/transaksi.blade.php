@extends('layouts.master')
@section('title')
    Daftar Transaksi
@endsection

@php
    use Illuminate\Support\Str;
@endphp

@section('css')
{{-- CSS Section --}}
<style>
    .invoice-modal .modal-dialog { max-width: 800px; }
    .invoice-modal .invoice-header { background-color: #f8f9fa; padding: 2rem; border-bottom: 1px solid #dee2e6; }
    .invoice-modal .invoice-logo { max-height: 60px; }
    .invoice-modal .invoice-details-table th,
    .invoice-modal .invoice-details-table td { border: none; }
    .clickable-price { cursor: pointer; color: inherit; text-decoration: none; }
    .clickable-price:hover { text-decoration: underline; color: #556ee6; }
    .sortable-header { cursor: pointer; position: relative; padding-right: 20px; }
    .sortable-header .sort-icon { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); opacity: 0.4; }
    .sortable-header.active .sort-icon { opacity: 1; color: #556ee6; }
    .advanced-filter-toggler { text-decoration: none; font-size: 0.9em; }
    a.disabled { pointer-events: none; opacity: 0.65; }
    .clickable-card {
        cursor: pointer;
        transition: transform 0.2s;
        display: block;
        color: inherit; 
    }
    .clickable-card:hover {
        transform: scale(1.03);
        color: inherit;
        text-decoration: none;
    }
    /* blur */
    .amount-container {
        position: relative;
        display: inline-block;
    }
    .amount-hidden {
        visibility: hidden;
        position: relative;
    }
    .amount-hidden::after {
        content: '******';
        visibility: visible;
        position: absolute;
        top: 0;
        left: 0;
        display: inline-block;
    }
    .toggle-amount-visibility {
        position: relative;
        z-index: 2;
        cursor: pointer;
        margin-left: 20px;
        color: #556ee6;
    }
    .toggle-amount-visibility:hover {
        color: #4458b8;
    }
</style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Daftar Transaksi', 'pagetitle' => 'Admin', 'breadcrumbs' => [['text' => 'Transaksi', 'url' => '']]])
    @endcomponent

    {{-- Summary Cards Row --}}
    <div class="row">
        <div class="col-lg-9">
            <div class="row">
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Belum Dibayar</p>
                                    <h4 class="mb-0 text-warning">
                                        <span class="amount-container" data-amount="Rp {{ number_format($totalBelumDibayar, 0, ',', '.') }}">
                                            <span class="amount-value">Rp {{ number_format($totalBelumDibayar, 0, ',', '.') }}</span>
                                            <i class="bx bx-show-alt toggle-amount-visibility" title="Tampilkan/Sembunyikan Nominal"></i>
                                        </span>
                                    </h4>
                                    <p class="text-muted mb-0 font-size-12">dari {{ $countBelumDibayar }} transaksi</p>
                                </div>
                                <div class="flex-shrink-0 align-self-center">
                                    <i class="bx bx-error-circle font-size-24 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">DP (Uang Muka)</p>
                                    <h4 class="mb-0 text-info">
                                        <span class="amount-container" data-amount="Rp {{ number_format($totalDpPaid, 0, ',', '.') }}">
                                            <span class="amount-value">Rp {{ number_format($totalDpPaid, 0, ',', '.') }}</span>
                                            <i class="bx bx-show-alt toggle-amount-visibility" title="Tampilkan/Sembunyikan Nominal"></i>
                                        </span>
                                    </h4>
                                    <p class="text-muted mb-0 font-size-12">dari {{ $countDp }} transaksi</p>
                                </div>
                                <div class="flex-shrink-0 align-self-center">
                                    <i class="bx bx-time-five font-size-24 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mini-stats-wid">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Lunas</p>
                                    <h4 class="mb-0 text-success">
                                        <span class="amount-container" data-amount="Rp {{ number_format($totalSudahDibayar, 0, ',', '.') }}">
                                            <span class="amount-value">Rp {{ number_format($totalSudahDibayar, 0, ',', '.') }}</span>
                                            <i class="bx bx-show-alt toggle-amount-visibility" title="Tampilkan/Sembunyikan Nominal"></i>
                                        </span>
                                    </h4>
                                    <p class="text-muted mb-0 font-size-12">dari {{ $countSudahDibayar }} transaksi</p>
                                </div>
                                <div class="flex-shrink-0 align-self-center">
                                    <i class="bx bx-check-circle font-size-24 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <a href="javascript:void(0);" id="profit-card-toggler" 
                data-filtered-profit="{{ $totalProfit }}" 
                data-overall-profit="{{ $totalOverallProfit }}" 
                class="clickable-card">
                <div class="card bg-primary">
                    <div class="card-body">
                        <div class="d-flex text-white">
                            <div class="flex-grow-1">
                                <p class="fw-medium text-white" id="profit-card-title">Total Pendapatan (Terfilter)</p>
                                <h4 class="mb-0 text-white" id="profit-card-value">
                                    <span class="amount-container" data-amount="Rp {{ number_format($totalProfit, 0, ',', '.') }}">
                                        <span class="amount-value">Rp {{ number_format($totalProfit, 0, ',', '.') }}</span>
                                        <i class="bx bx-show-alt toggle-amount-visibility text-white" title="Tampilkan/Sembunyikan Nominal"></i>
                                    </span>
                                </h4>
                                <small class="mb-0 opacity-75" id="profit-card-subtitle">Klik untuk lihat total keseluruhan</small>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <i class="bx bx-wallet font-size-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Interactive Filter Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('transaksi.index') }}" method="GET">
                        <div class="row">
                            <div class="col-12">
                                <label for="search" class="form-label">Cari Transaksi</label>
                                <input type="text" name="search" class="form-control" placeholder="Cari nama pelanggan atau kode invoice..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="mt-2">
                             <a class="advanced-filter-toggler" data-bs-toggle="collapse" href="#advancedFilters" role="button" aria-expanded="false" aria-controls="advancedFilters">
                                <i class="bx bx-slider-alt me-1"></i> Filter Lanjutan
                            </a>
                        </div>

                        @php
                            $isAdvancedFilterActive = request()->filled('payment_status') || request()->filled('process_status') || request()->filled('packet_id') || request()->filled('start_date') || request()->filled('end_date');
                        @endphp

                        <div class="collapse {{ $isAdvancedFilterActive ? 'show' : '' }}" id="advancedFilters">
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="payment_status" class="form-label">Status Pembayaran</label>
                                    <select class="form-select" name="payment_status" id="payment_status">
                                        <option value="">Semua</option>
                                        @foreach($paymentStatuses as $status)
                                            <option value="{{ $status }}" {{ request('payment_status') == $status ? 'selected' : '' }}>
                                                {{ $status == 'dp' ? 'DP (Uang Muka)' : ($status == 'sudah dibayar' ? 'Lunas' : 'Belum Dibayar') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="process_status" class="form-label">Status Pengerjaan</label>
                                    <select class="form-select" name="process_status" id="process_status">
                                        <option value="">Semua</option>
                                        @foreach($processStatuses as $status)
                                            <option value="{{ $status }}" {{ request('process_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="packet_id" class="form-label">Paket</label>
                                    <select class="form-select" name="packet_id" id="packet_id">
                                        <option value="">Semua Paket</option>
                                        @foreach($packetsForFilter as $productName => $packets)
                                            <optgroup label="{{ $productName }}">
                                                @foreach($packets as $packet)
                                                    <option value="{{ $packet->id }}" {{ request('packet_id') == $packet->id ? 'selected' : '' }}>
                                                        {{ $packet->name }} - {{ $productName }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <label class="form-label mb-0">Rentang Tanggal</label>
                                </div>
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label small text-muted">Dari</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label small text-muted">Sampai</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                             <a href="{{ route('transaksi.index') }}" class="btn btn-secondary"><i class="bx bx-reset me-1"></i> Reset</a>
                            <button type="submit" class="btn btn-primary"><i class="bx bx-filter-alt me-1"></i> Terapkan Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a href="{{ route('transaksi.create') }}" class="btn btn-success waves-effect waves-light"><i class="mdi mdi-plus me-2"></i> Tambah Transaksi Baru</a>
                                <button type="button" id="hide-completed-btn" class="btn btn-secondary waves-effect waves-light">
                                    <i class="bx bx-hide me-1"></i> Sembunyikan Selesai
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    @php
                                        function sortable_header($label, $column, $request) {
                                            $sortBy = $request->input('sort_by');
                                            $sortDirection = $request->input('sort_direction', 'asc');
                                            $isActive = ($sortBy === $column);
                                            $newDirection = ($isActive && $sortDirection === 'asc') ? 'desc' : 'asc';
                                            $icon = $isActive ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort';
                                            $url = $request->fullUrlWithQuery(['sort_by' => $column, 'sort_direction' => $newDirection]);
                                            return '<a href="' . $url . '" class="text-dark sortable-header' . ($isActive ? ' active' : '') . '">' . $label . '<i class="bx ' . $icon . ' sort-icon"></i></a>';
                                        }
                                    @endphp
                                    <th>Kode Invoice</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th>{!! sortable_header('Total Biaya', 'total_price', request()) !!}</th>
                                    <th>{!! sortable_header('Tanggal', 'created_at', request()) !!}</th>
                                    <th>Status Pembayaran</th>
                                    <th>Status Pengerjaan</th>
                                    <th>Detail</th>
                                    <th style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentStatusConfig = [
                                        'belum dibayar' => ['label' => 'Belum Dibayar', 'icon' => '🟡', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'dp' => ['label' => 'DP (Uang Muka)', 'icon' => '🔵', 'class' => 'bg-info-subtle text-info-emphasis'],
                                        'sudah dibayar' => ['label' => 'Lunas', 'icon' => '🟢', 'class' => 'bg-success-subtle text-success-emphasis'],
                                    ];
                                    
                                    // KONFIGURASI STATUS BARU
                                    $processStatusConfig = [
                                        'Pelanggan Belum Foto' => ['icon' => '📷❌', 'class' => 'bg-light text-dark'],
                                        'Pelanggan Pilih Foto' => ['icon' => '🖼️', 'class' => 'bg-info-subtle text-info-emphasis'],
                                        'Proses Edit' => ['icon' => '✏️', 'class' => 'bg-primary-subtle text-primary-emphasis'],
                                        'Proses Cetak' => ['icon' => '🖨️', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'Selesai' => ['icon' => '✅', 'class' => 'bg-success-subtle text-success-emphasis']
                                    ];
                                @endphp
                                @forelse($transactions as $transaksi)
                                    <tr data-process-status="{{ $transaksi->process_status }}" data-payment-status="{{ $transaksi->status }}">
                                        <td><a href="javascript: void(0);" class="text-body fw-bold">{{ $transaksi->receipt_code }}</a></td>
                                        
                                        {{-- MODIFIKASI: Menambahkan Nomor HP & Icon --}}
                                        <td>
                                            <h6 class="mb-1">{{ $transaksi->customer_name }}</h6>
                                            <div class="text-muted" style="font-size: 0.85em;">
                                                <i class="bx bx-phone text-secondary me-1"></i>
                                                {{ $transaksi->phone_number }}
                                            </div>
                                        </td>

                                        <td>
                                            <span class="fw-bold">{{ $transaksi->packet->product->name ?? 'N/A' }}</span>
                                            @if($transaksi->packet && $transaksi->packet->product)
                                                <br>
                                                <small class="text-muted">{{ $transaksi->packet->name }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-bold"><a href="javascript:void(0);" class="clickable-price" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</a></td>
                                        <td>{{ $transaksi->created_at->format('d M Y, H:i') }}</td>
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST" class="status-update-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="field" value="status">
                                                <select name="value" class="form-select form-select-sm payment-status-select {{ $paymentStatusConfig[$transaksi->status]['class'] ?? '' }}" data-transaction-id="{{ $transaksi->transaction_id }}">
                                                    @foreach ($paymentStatusConfig as $statusKey => $config)
                                                        <option value="{{ $statusKey }}" {{ $transaksi->status == $statusKey ? 'selected' : '' }}>
                                                            {{ $config['icon'] }} {{ $config['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="field" value="process_status">
                                                
                                                @php
                                                    $statusKeys = array_keys($processStatusConfig);
                                                    $currentIndex = array_search($transaksi->process_status, $statusKeys);
                                                    if ($currentIndex === false) $currentIndex = 0;
                                                @endphp

                                                <select name="value" class="form-select form-select-sm {{ $processStatusConfig[$transaksi->process_status]['class'] ?? '' }}" onchange="this.form.submit()">
                                                     @foreach ($processStatusConfig as $status => $config)
                                                        @php
                                                            $loopIndex = array_search($status, $statusKeys);
                                                            $disabled = false;
                                                            $labelSuffix = '';

                                                            // --- LOGIKA SEQUENTIAL (Mencegah Loncat Status) ---
                                                            if ($loopIndex > $currentIndex) {
                                                                if ($loopIndex == $currentIndex + 1) {
                                                                    // Langkah selanjutnya: OK
                                                                } else {
                                                                    $canSkip = true;
                                                                    for ($k = $currentIndex + 1; $k < $loopIndex; $k++) {
                                                                        $skippedStatus = $statusKeys[$k];
                                                                        if ($skippedStatus === 'Proses Cetak' && !$transaksi->hasPrintableItems()) {
                                                                            continue;
                                                                        }
                                                                        $canSkip = false;
                                                                        break;
                                                                    }
                                                                    if (!$canSkip) $disabled = true;
                                                                }
                                                            }

                                                            // --- LOGIKA VALIDASI DATA ---
                                                            if ($status === 'Pelanggan Pilih Foto' && empty($transaksi->url_images)) {
                                                                $disabled = true; 
                                                                $labelSuffix = '(Isi Link Dulu)';
                                                            }
                                                            if ($status === 'Proses Cetak' && !$transaksi->hasPrintableItems()) {
                                                                $disabled = true;
                                                                $labelSuffix = '(Tidak ada item cetak)';
                                                            }
                                                            if ($status === 'Selesai') {
                                                                if ($transaksi->status !== 'sudah dibayar') {
                                                                    $disabled = true; $labelSuffix = '(Belum Lunas)';
                                                                } elseif (empty($transaksi->url_photos_result)) {
                                                                    $disabled = true; $labelSuffix = '(Isi Link Final)';
                                                                }
                                                            }
                                                        @endphp
                                                         <option value="{{ $status }}" 
                                                            {{ $transaksi->process_status == $status ? 'selected' : '' }}
                                                            {{ $disabled ? 'disabled' : '' }}>
                                                             {{ $config['icon'] }} {{ $status }} {{ $labelSuffix }}
                                                         </option>
                                                     @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm btn-rounded" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Lihat</button>
                                        </td>
                                        <td>
                                           <div class="d-flex align-items-center gap-2">
                                                {{-- Action Buttons for Links --}}
                                                <button type="button" class="btn btn-sm btn-info update-url-btn" 
                                                        data-id="{{ $transaksi->transaction_id }}"
                                                        data-field="url_images"
                                                        data-value="{{ $transaksi->url_images }}"
                                                        data-bs-toggle="tooltip" title="Input Link Galeri (Pemilihan Foto)">
                                                    <i class="mdi mdi-image-multiple"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-secondary update-url-btn"
                                                        data-id="{{ $transaksi->transaction_id }}"
                                                        data-field="url_photos_result"
                                                        data-value="{{ $transaksi->url_photos_result }}"
                                                        data-bs-toggle="tooltip" title="Input Link Hasil Akhir">
                                                    <i class="bx bx-check-double"></i>
                                                </button>

                                                {{-- INPUT MANUAL SELECTION BUTTON --}}
                                                @php
                                                    $canInputSelections = !empty($transaksi->url_images);
                                                    $tooltipMessage = $canInputSelections ? "Input Pilihan Foto (Paste WA)" : "Isi Link Galeri Terlebih Dahulu";
                                                    $existingText = "";
                                                    if($transaksi->select_edit_photo) $existingText .= "*DAFTAR FOTO EDIT*\n" . $transaksi->select_edit_photo . "\n\n";
                                                    if($transaksi->select_print_photo) $existingText .= "*DAFTAR FOTO CETAK*\n" . $transaksi->select_print_photo;
                                                @endphp
                                                <button type="button" class="btn btn-sm btn-warning input-selection-btn"
                                                        data-id="{{ $transaksi->transaction_id }}"
                                                        data-existing-text="{{ $existingText }}"
                                                        {{ !$canInputSelections ? 'disabled' : '' }}
                                                        data-bs-toggle="tooltip" title="{{ $tooltipMessage }}">
                                                    <i class="bx bx-list-check"></i>
                                                </button>
                                                
                                                <a href="{{ route('transaksi.edit', $transaksi->transaction_id) }}" class="text-primary" data-bs-toggle="tooltip" title="Edit Transaksi"><i class="uil uil-pen font-size-18"></i></a>

                                                <form id="delete-form-{{ $transaksi->transaction_id }}" action="{{ route('transaksi.destroy', $transaksi->transaction_id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-link text-danger p-0 delete-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#confirmDeleteModal" 
                                                            data-form-id="delete-form-{{ $transaksi->transaction_id }}"
                                                            data-bs-toggle="tooltip" title="Hapus Transaksi">
                                                        <i class="uil uil-trash-alt font-size-18"></i>
                                                    </button>
                                                </form>

                                                @if(!empty($transaksi->phone_number) && $transaksi->user)
                                                    @php
                                                        // TEMPLATE PESAN WA BARU
                                                        
                                                        $hasPrint = $transaksi->hasPrintableItems();
                                                        $linkFinal = $transaksi->url_photos_result ? $transaksi->url_photos_result : "[Link Belum Diisi]";
                                                        $packetName = $transaksi->packet->name ?? 'N/A';
                                                        $productName = $transaksi->packet->product->name ?? 'N/A';
                                                        
                                                        // Pesan Tambahan: Backup Reminder & Info Paket
                                                        $backupNote = "Catatan Penting:\nMohon segera unduh dan backup foto Anda. Link drive akan kadaluarsa/dihapus dalam 14 hari.";
                                                        $backupNoteSelesai = "Catatan Penting:\nMohon segera unduh dan backup foto Anda. Link drive akan kadaluarsa/dihapus dalam 30 hari.";
                                                        $detailPaket = "Detail Paket:\n*{$productName} - {$packetName}*";

                                                        // Isi pesan 'Selesai'
                                                        if ($hasPrint) {
                                                            $pesanSelesai = "Halo Kak *{$transaksi->customer_name}*, kabar gembira! Foto Anda telah selesai dicetak & diedit.\n\n{$detailPaket}\n\nBerikut link softfile foto finalnya:\n{$linkFinal}\n\n{$backupNoteSelesai}\n\nRincian pesanan atas:\nNama : {$transaksi->customer_name}\nNo. Nota : {$transaksi->receipt_code}\n\nSilakan ambil hasil cetak di studio kami. Terima kasih!\n\nJika kakak berkenan, boleh beri rating layanan kami di sini : https://g.page/r/CR-YHaNKJ2C_EBM/review";
                                                        } else {
                                                            $pesanSelesai = "Halo Kak *{$transaksi->customer_name}*, kabar gembira! Foto Anda telah selesai diedit.\n\n{$detailPaket}\n\nBerikut link softfile foto finalnya:\n{$linkFinal}\n\n{$backupNoteSelesai}\n\nRincian pesanan atas:\nNama : {$transaksi->customer_name}\nNo. Nota : {$transaksi->receipt_code}\n\nTerima kasih telah mempercayakan momennya di Yunas Studio!\n\nJika kakak berkenan, boleh beri rating layanan kami di sini : https://g.page/r/CR-YHaNKJ2C_EBM/review";
                                                        }

                                                        $waMessages = [
                                                            'Pelanggan Belum Foto' => "Halo kak {$transaksi->customer_name}, jadwal foto belum terlaksana. Hubungi kami untuk info lebih lanjut.",
                                                            'Selesai' => $pesanSelesai
                                                        ];

                                                        // Template Pelanggan Pilih Foto
                                                        if ($transaksi->process_status === 'Pelanggan Pilih Foto') {
                                                            $maxEdit = $transaksi->packet->max_photos_for_edit ?? 0;
                                                            
                                                            $editList = "";
                                                            for ($i = 1; $i <= $maxEdit; $i++) {
                                                                $editList .= "{$i}. \n";
                                                            }

                                                            $printList = "";
                                                            if ($transaksi->packet && $transaksi->packet->combined_defaults) {
                                                                foreach ($transaksi->packet->combined_defaults as $item) {
                                                                    if (stripos($item->name, 'cetak') !== false || stripos($item->name, 'print') !== false) {
                                                                        for ($q = 0; $q < $item->quantity; $q++) {
                                                                            $printList .= "- {$item->name} : \n";
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            if ($transaksi->additionals) {
                                                                foreach ($transaksi->additionals as $additional) {
                                                                    if (stripos($additional->name, 'cetak') !== false || stripos($additional->name, 'print') !== false) {
                                                                        for ($q = 0; $q < $additional->pivot->quantity; $q++) {
                                                                            $printList .= "- (Extra) {$additional->name} : \n";
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            
                                                            if (empty($printList)) {
                                                                $printList = "- (Tidak ada item cetak) \n";
                                                            }

                                                            $linkGaleri = $transaksi->url_images ? $transaksi->url_images : "[Link Belum Diisi]";
                                                            
                                                            // Updated with Backup Note
                                                            $waMessages['Pelanggan Pilih Foto'] = "Halo kak *{$transaksi->customer_name}*, Terima kasih sudah mempercayakan momennya di Yunas Studio.\n\nDetail Paket:\n*{$productName} - {$packetName}*\n\nBerikut kami kirimkan link untuk pemilihan foto:\n{$linkGaleri}\n\n{$backupNote}\n\nMohon untuk mengisi format pemilihan foto dibawah ini dengan menyalin pesan ini dan mengisi nama fotonya (misal : 1.YNSFXXX):\n\n*DAFTAR FOTO EDIT (Max {$maxEdit} Foto)*\n{$editList}\n*DAFTAR FOTO CETAK*\n{$printList}\nTerima kasih";
                                                        }

                                                        // Tooltip WA
                                                        $waTooltip = "Kirim WhatsApp";
                                                        if($transaksi->process_status == 'Pelanggan Belum Foto') $waTooltip = "Ingatkan Jadwal Foto";
                                                        elseif($transaksi->process_status == 'Pelanggan Pilih Foto') $waTooltip = "Kirim Link Pilih Foto";
                                                        elseif($transaksi->process_status == 'Selesai') $waTooltip = "Info Pengambilan Foto";

                                                        $waLink = null;
                                                        if (isset($waMessages[$transaksi->process_status])) {
                                                            $waMessage = $waMessages[$transaksi->process_status];
                                                            $phoneNumber = preg_replace('/\D/', '', $transaksi->phone_number);
                                                            if (strpos($phoneNumber, '0') === 0) {
                                                                $phoneNumber = '62' . substr($phoneNumber, 1);
                                                            }
                                                            $waLink = "https://api.whatsapp.com/send?phone={$phoneNumber}&text=" . urlencode($waMessage);
                                                        }
                                                    @endphp
                                                    @if(isset($waLink))
                                                        <a href="{{ $waLink }}" target="_blank" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="{{ $waTooltip }}"><i class="uil uil-whatsapp"></i></a>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">Data transaksi tidak ditemukan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                     <div class="row mt-4">
                        <div class="col-sm-6 d-flex align-items-center">
                            <div>
                                <p class="mb-sm-0">Menampilkan {{ $transactions->firstItem() }} sampai {{ $transactions->lastItem() }} dari {{ $transactions->total() }} data</p>
                            </div>
                            <div class="ms-3">
                                <form method="GET" action="{{ route('transaksi.index') }}" class="d-flex align-items-center">
                                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ is_array($value) ? http_build_query($value) : $value }}">
                                    @endforeach
                                    <label for="per_page" class="form-label me-2 mb-0">Tampil:</label>
                                    <select name="per_page" id="per_page" class="form-select form-select-sm" style="width: 70px;" onchange="this.form.submit()">
                                        @foreach($perPageOptions as $option)
                                            <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-end">
                                {{ $transactions->withQueryString()->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Details, Delete Confirmation, DP Amount (Scripts at bottom) --}}
    @foreach($transactions as $transaksi)
        <div id="detailModal{{ $transaksi->transaction_id }}" class="modal fade invoice-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="invoice-content" id="invoiceContent{{ $transaksi->transaction_id }}">
                            <div class="invoice-header text-center"><div class="mb-3"><img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="logo" class="invoice-logo"/></div><p class="text-muted mb-0">Jalan Lingkar Selatan, Sukabumi</p></div>
                            <div class="p-4">
                                <div class="row"><div class="col-md-6"><h5 class="font-size-16">Ditagihkan Kepada:</h5><p class="mb-1">{{ $transaksi->customer_name }}</p>@if($transaksi->phone_number)<p class="mb-1 text-muted">{{ $transaksi->phone_number }}</p>@endif @if($transaksi->user)<p class="mb-1 text-muted"><i class="mdi mdi-account-circle-outline me-1"></i> Akun User: {{ $transaksi->user->name }}</p>@endif</div><div class="col-md-6 text-md-end"><h5 class="font-size-16">Rincian Invoice:</h5><p class="mb-1"><strong>No. Invoice:</strong> {{ $transaksi->receipt_code }}</p><p class="mb-1"><strong>Tanggal Transaksi:</strong> {{ $transaksi->created_at->format('d M Y, H:i') }}</p><p class="mb-1"><strong>Status Pembayaran:</strong> {{ $paymentStatusConfig[$transaksi->status]['label'] ?? ucwords($transaksi->status) }}</p><p class="mb-1"><strong>Status Pengerjaan:</strong> {{ $transaksi->process_status }}</p></div></div>
                                <div class="py-2 mt-3"><h3 class="font-size-15 fw-bold">Ringkasan Pesanan</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap">
                                        <thead class="table-light"><tr><th style="width: 70px;">No.</th><th>Item</th><th class="text-end">Harga</th><th class="text-center">Jml</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @if($transaksi->packet)
                                                <tr>
                                                    <td>1</td>
                                                    <td><h5 class="font-size-15 mb-0">{{ $transaksi->packet->name }}</h5><span class="text-muted">{{ $transaksi->packet->product->name ?? '' }}</span></td>
                                                    <td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                                                    <td class="text-center">1</td>
                                                    <td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                                                </tr>
                                            @endif
                                            @if($transaksi->packet && $transaksi->packet->printOptions->isNotEmpty())
                                                <tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted small">Cetak Termasuk:</strong></td></tr>
                                                @foreach($transaksi->packet->printOptions as $printOption)
                                                    <tr class="bg-light">
                                                        <td><i class="mdi mdi-circle-small text-muted"></i></td>
                                                        <td>
                                                            <span class="text-dark">Include Cetak {{ $printOption->name }}</span>
                                                        </td>
                                                        <td class="text-end text-muted small">(Included)</td>
                                                        <td class="text-center">{{ $printOption->pivot->quantity }}</td>
                                                        <td class="text-end">-</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            @if($transaksi->packet && $transaksi->packet->additionalDefaults->isNotEmpty())
                                                <tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted small">Tambahan Termasuk:</strong></td></tr>
                                                @foreach($transaksi->packet->additionalDefaults as $default)
                                                    <tr>
                                                        <td><i class="mdi mdi-circle-small text-muted"></i></td>
                                                        <td colspan="4">{{ $default->quantity }}x {{ $default->additional->name }}</td>
                                                    </tr>
                                                @endforeach 
                                            @endif
                                            @if($transaksi->additionals->isNotEmpty())
                                                <tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted small">Item Tambahan:</strong></td></tr>
                                                @foreach($transaksi->additionals as $additional)
                                                    <tr>
                                                        <td><i class="mdi mdi-circle-small text-muted"></td>
                                                        <td><h5 class="font-size-15 mb-0">{{ $additional->name }}</h5><span class="text-muted">Item Tambahan</span></td>
                                                        <td class="text-end">Rp {{ number_format($additional->pivot->price, 0, ',', '.') }}</td>
                                                        <td class="text-center">{{ $additional->pivot->quantity }}</td>
                                                        <td class="text-end">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach 
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <div class="w-50">
                                        <table class="table table-nowrap invoice-details-table">
                                            <tbody>
                                                <tr><td class="fw-bold">Subtotal</td><td class="text-end">Rp {{ number_format($transaksi->total_price + $transaksi->discount, 0, ',', '.') }}</td></tr>
                                                @if ($transaksi->discount > 0)<tr class="text-danger"><td class="fw-bold">Diskon</td><td class="text-end">- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</td></tr>@endif
                                                <tr class="border-top"><td class="fw-bold">Total Akhir</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @if($transaksi->status == 'dp' && isset($transaksi->dp_amount))
                                                    <tr><td class="fw-bold">DP Terbayar</td><td class="text-end">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                    <tr class="fs-5 bg-light"><td class="fw-bold">Sisa Tagihan</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                @elseif($transaksi->status == 'sudah dibayar')
                                                    <tr class="fs-5 bg-light"><td class="fw-bold">Total Terbayar</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @else
                                                     <tr class="fs-5 bg-light"><td class="fw-bold">Total Tagihan</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @if($transaksi->note)<hr><div class="py-2"><h5 class="font-size-15">Catatan:</h5><p class="text-muted fst-italic">{{ $transaksi->note }}</p></div>@endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><a href="{{ route('transaksi.print-invoice', $transaksi) }}" target="_blank" class="btn btn-primary"><i class="mdi mdi-printer me-1"></i> Cetak Nota</a></div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bx bx-error-circle bx-lg text-danger mb-2"></i>
                        <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                        <p class="text-muted">Tindakan ini tidak dapat dibatalkan dan akan menghapus folder foto terkait.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dpModal = new bootstrap.Modal(document.getElementById('dpAmountModal'));
    const dpForm = document.getElementById('dpAmountForm');
    const dpInput = document.getElementById('dp_amount_modal');

    // Init URL Modal
    const urlModal = new bootstrap.Modal(document.getElementById('urlModal'));
    const urlForm = document.getElementById('urlForm');
    const urlInput = document.getElementById('url_input');
    const urlLabel = document.getElementById('urlModalLabel');
    const urlFieldInput = document.getElementById('url_field_input');

    // Init Selection Modal
    const selectionModal = new bootstrap.Modal(document.getElementById('inputSelectionModal'));
    const selectionForm = document.getElementById('selectionForm');
    const selectionTextInput = document.getElementById('selection_text_input');

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        let formToSubmit = null;

        confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const formId = button.getAttribute('data-form-id');
            formToSubmit = document.getElementById(formId);
        });

        confirmDeleteBtn.addEventListener('click', function () {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    }

    document.querySelectorAll('.payment-status-select').forEach(selectElement => {
        selectElement.addEventListener('change', function (e) {
            const selectedStatus = e.target.value;
            const form = e.target.closest('form');

            if (selectedStatus === 'dp') {
                dpForm.action = form.action;
                dpModal.show();
            } else {
                form.submit();
            }
        });
    });

    document.getElementById('dpAmountModal').addEventListener('shown.bs.modal', function () {
        dpInput.focus();
    });

    // Handle Update URL Buttons
    document.querySelectorAll('.update-url-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionId = this.dataset.id;
            const field = this.dataset.field;
            const currentValue = this.dataset.value;
            
            // Set Modal Title based on field
            if (field === 'url_images') {
                urlLabel.textContent = 'Update Link Galeri (Pemilihan Foto)';
                urlInput.placeholder = 'https://...';
            } else {
                urlLabel.textContent = 'Update Link Hasil Akhir (Final)';
                urlInput.placeholder = 'https://...';
            }

            urlInput.value = currentValue;
            urlFieldInput.value = field;
            
            // Set Form Action (Reuse existing update-status route or generic update)
            // Using update-status route since it handles field/value logic
            urlForm.action = `/transaksi/${transactionId}/update-status`;
            
            urlModal.show();
        });
    });

    document.getElementById('urlModal').addEventListener('shown.bs.modal', function () {
        urlInput.focus();
    });

    // Handle Input Selection Button (NEW)
    document.querySelectorAll('.input-selection-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionId = this.dataset.id;
            const existingText = this.dataset.existingText;
            
            document.getElementById('selection_text_input').value = existingText;
            document.getElementById('selectionForm').action = `/transaksi/${transactionId}/update-selections`;
            
            selectionModal.show();
        });
    });

    const profitToggles = document.querySelectorAll('.profit-toggle');
    const profitAmountEl = document.getElementById('profit-card-amount');
    const profitTitleEl = document.getElementById('profit-card-title');

    profitToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const newAmount = parseFloat(this.dataset.amount);
            const newTitle = this.dataset.title;
            profitAmountEl.textContent = 'Rp ' + newAmount.toLocaleString('id-ID');
            profitTitleEl.textContent = newTitle;
            profitToggles.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    document.querySelectorAll('.toggle-amount-visibility').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const amountContainer = this.closest('.amount-container');
            const amountValue = amountContainer.querySelector('.amount-value');
            
            if (amountValue.classList.contains('amount-hidden')) {
                amountValue.classList.remove('amount-hidden');
                this.classList.remove('bx-hide');
                this.classList.add('bx-show-alt');
            } else {
                amountValue.classList.add('amount-hidden');
                this.classList.remove('bx-show-alt');
                this.classList.add('bx-hide');
            }
        });
    });

    // Hide Completed Logic
    const toggleBtn = document.getElementById('hide-completed-btn');
    const processStatusToHide = 'Selesai';
    const paymentStatusToHide = 'sudah dibayar';

    function updateView(isHidden) {
        const rows = document.querySelectorAll(`tr[data-process-status]`);
        rows.forEach(row => {
            const isCompleted = row.dataset.processStatus === processStatusToHide && row.dataset.paymentStatus === paymentStatusToHide;
            if (isCompleted && isHidden) {
                row.style.display = 'none';
            } else {
                row.style.display = '';
            }
        });

        if (isHidden) {
            toggleBtn.innerHTML = `<i class="bx bx-show me-1"></i> Tampilkan Selesai`;
            toggleBtn.classList.remove('btn-secondary');
            toggleBtn.classList.add('btn-info');
        } else {
            toggleBtn.innerHTML = `<i class="bx bx-hide me-1"></i> Sembunyikan Selesai`;
            toggleBtn.classList.remove('btn-info');
            toggleBtn.classList.add('btn-secondary');
        }
    }

    let isCompletedHidden = localStorage.getItem('hideCompleted') === 'true';
    updateView(isCompletedHidden);

    toggleBtn.addEventListener('click', function() {
        isCompletedHidden = !isCompletedHidden;
        localStorage.setItem('hideCompleted', isCompletedHidden);
        updateView(isCompletedHidden);
    });

    const profitCard = document.getElementById('profit-card-toggler');
    if (profitCard) {
        const titleEl = document.getElementById('profit-card-title');
        const valueEl = document.getElementById('profit-card-value');
        const subtitleEl = document.getElementById('profit-card-subtitle');

        const filteredProfit = parseFloat(profitCard.dataset.filteredProfit);
        const overallProfit = parseFloat(profitCard.dataset.overallProfit);
        
        let isShowingFiltered = true;

        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });

        profitCard.addEventListener('click', function(event) {
            event.preventDefault(); 
            isShowingFiltered = !isShowingFiltered;

            if (isShowingFiltered) {
                titleEl.textContent = 'Total Pendapatan (Terfilter)';
                const amountContainer = valueEl.querySelector('.amount-container');
                if (amountContainer) {
                    const amountValue = amountContainer.querySelector('.amount-value');
                    if (amountValue) {
                        amountValue.textContent = formatter.format(filteredProfit);
                    } else {
                        valueEl.innerHTML = `<span class="amount-container" data-amount="${formatter.format(filteredProfit)}"><span class="amount-value">${formatter.format(filteredProfit)}</span><i class="bx bx-show-alt toggle-amount-visibility text-white" title="Tampilkan/Sembunyikan Nominal"></i></span>`;
                    }
                } else {
                    valueEl.innerHTML = `<span class="amount-container" data-amount="${formatter.format(filteredProfit)}"><span class="amount-value">${formatter.format(filteredProfit)}</span><i class="bx bx-show-alt toggle-amount-visibility text-white" title="Tampilkan/Sembunyikan Nominal"></i></span>`;
                }
                subtitleEl.textContent = 'Klik untuk lihat total keseluruhan';
            } else {
                titleEl.textContent = 'Total Pendapatan (Keseluruhan)';
                const amountContainer = valueEl.querySelector('.amount-container');
                if (amountContainer) {
                    const amountValue = amountContainer.querySelector('.amount-value');
                    if (amountValue) {
                        amountValue.textContent = formatter.format(overallProfit);
                    } else {
                        valueEl.innerHTML = `<span class="amount-container" data-amount="${formatter.format(overallProfit)}"><span class="amount-value">${formatter.format(overallProfit)}</span><i class="bx bx-show-alt toggle-amount-visibility text-white" title="Tampilkan/Sembunyikan Nominal"></i></span>`;
                    }
                } else {
                    valueEl.innerHTML = `<span class="amount-container" data-amount="${formatter.format(overallProfit)}"><span class="amount-value">${formatter.format(overallProfit)}</span><i class="bx bx-show-alt toggle-amount-visibility text-white" title="Tampilkan/Sembunyikan Nominal"></i></span>`;
                }
                subtitleEl.textContent = 'Klik untuk lihat total terfilter';
                const hideCompletedBtn = document.getElementById('hide-completed-btn');
                if (hideCompletedBtn) {
                    hideCompletedBtn.style.display = 'none';
                }
            }
        });
    }
});
</script>
@endsection

@section('script-bottom')
<div class="modal fade" id="dpAmountModal" tabindex="-1" aria-labelledby="dpAmountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dpAmountForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="field" value="status">
                <input type="hidden" name="value" value="dp">
                <div class="modal-header">
                    <h5 class="modal-title" id="dpAmountModalLabel">Masukkan Jumlah DP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dp_amount_modal" class="form-label">Jumlah DP (Rp)</label>
                        <input type="number" class="form-control" id="dp_amount_modal" name="dp_amount" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan DP</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- URL Input Modal (NO REQUIRED) --}}
<div class="modal fade" id="urlModal" tabindex="-1" aria-labelledby="urlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="urlForm" method="POST">
                @csrf
                @method('PUT')
                {{-- Field name will be dynamic --}}
                <input type="hidden" id="url_field_input" name="field" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="urlModalLabel">Update Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="url_input" class="form-label">URL / Link</label>
                        {{-- REMOVED REQUIRED ATTRIBUTE --}}
                        <input type="url" class="form-control" id="url_input" name="value" placeholder="https://...">
                        <small class="text-muted">Pastikan link diawali dengan https://</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Link</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Selection Input Modal (UPDATED: Single Textarea) --}}
<div class="modal fade" id="inputSelectionModal" tabindex="-1" aria-labelledby="inputSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="selectionForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-header">
                    <h5 class="modal-title" id="inputSelectionModalLabel">Input Pilihan Foto Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-1"></i> Salin dan tempel (Paste) seluruh pesan balasan dari WhatsApp pelanggan ke kolom di bawah ini. Sistem akan otomatis memisahkan daftar foto edit dan cetak.
                    </div>
                    
                    <div class="mb-3">
                        <label for="selection_text_input" class="form-label fw-bold">Pesan Balasan WhatsApp</label>
                        <textarea class="form-control" id="selection_text_input" name="selection_text" rows="15" placeholder="Contoh:
*DAFTAR FOTO EDIT (Max 10 Foto)*
1. 1234
2. 5678

*DAFTAR FOTO CETAK*
- 10R : 1234"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pilihan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection