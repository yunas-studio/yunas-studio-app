@extends('layouts.master')

@section('title') Dashboard @endsection

@section('css')
<style>
    /* Styling for Advanced Table & Modals */
    .invoice-modal .modal-dialog { max-width: 800px; }
    .invoice-modal .invoice-header { background-color: #f8f9fa; padding: 2rem; border-bottom: 1px solid #dee2e6; }
    .invoice-modal .invoice-logo { max-height: 60px; }
    .invoice-modal .invoice-details-table th,
    .invoice-modal .invoice-details-table td { border: none; }
    .clickable-price { cursor: pointer; color: inherit; text-decoration: none; }
    .clickable-price:hover { text-decoration: underline; color: #556ee6; }
    .cursor-pointer { cursor: pointer; }
</style>
@endsection

@section('content')

    @component('common-components.breadcrumb')
        @slot('pagetitle') Yunas Studio @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    <div class="row">
        {{-- Total Income Card with Filter --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end">
                        <div class="dropdown">
                            <a class="dropdown-toggle text-reset" href="#" id="dropdownMenuButtonIncome"
                                data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="fw-semibold">Filter:</span> <span class="text-muted" id="income-sort-label">Bulan Ini</span> <i class="mdi mdi-chevron-down ms-1"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButtonIncome">
                                <a class="dropdown-item income-filter cursor-pointer" data-type="total">Total (Akumulasi)</a>
                                <a class="dropdown-item income-filter cursor-pointer" data-type="monthly">Bulan Ini</a>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="mb-1 mt-1">
                            Rp <span id="total-income-display" 
                                     data-total="{{ $totalIncome }}" 
                                     data-monthly="{{ $totalIncomeMonthly }}">
                                     {{ number_format($totalIncomeMonthly, 0, ',', '.') }}
                               </span>
                        </h4>
                        <p class="text-muted mb-0">Total Pemasukan</p>
                    </div>
                    
                    <p class="text-muted mt-3 mb-0" id="income-subtext">
                        <span class="text-info me-1"><i class="mdi mdi-calendar-month me-1"></i></span> Bulan Ini
                    </p>
                </div>
            </div>
        </div>

        {{-- Total Transactions Card --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div id="orders-chart" data-colors='["--bs-success"]'> </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $total_transactions }}</span></h4>
                        <p class="text-muted mb-0">Total Transaksi</p>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="text-success me-1"><i class="mdi mdi-check-all me-1"></i>{{ $completed_transactions }}</span> Selesai</p>
                </div>
            </div>
        </div>

        {{-- Pending Transactions Card (Updated) --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div id="customers-chart" data-colors='["--bs-danger"]'> </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $pending_transactions }}</span></h4>
                        {{-- UPDATED TEXT HERE --}}
                        <p class="text-muted mb-0">Belum Lunas / DP</p>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="text-danger me-1"><i class="mdi mdi-alert-circle-outline me-1"></i></span> Perlu Tindakan</p>
                </div>
            </div>
        </div>

        {{-- Profit Card --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div id="growth-chart" data-colors='["--bs-warning"]'></div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1">Rp <span data-plugin="counterup">{{ number_format($profit, 0, ',', '.') }}</span></h4>
                        <p class="text-muted mb-0">Keuntungan Bersih</p>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="text-success me-1"><i class="mdi mdi-wallet me-1"></i></span> (Income - Expense)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Sales Analytics Chart --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="float-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-light chart-filter active" data-filter="daily">Daily</button>
                            <button type="button" class="btn btn-light chart-filter" data-filter="monthly">Monthly</button>
                            <button type="button" class="btn btn-light chart-filter" data-filter="yearly">Yearly</button>
                        </div>
                    </div>
                    <h4 class="card-title mb-4">Analisis Pemasukan</h4>

                    <div class="mt-3">
                        <div id="sales-analytics-chart" class="apex-charts" dir="ltr" style="min-height: 450px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Selling Packets --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Paket Terlaris</h4>

                    <div style="max-height: 450px; overflow-y: auto;">
                        @foreach($top_selling_packets as $item)
                        <div class="row align-items-center g-0 mt-3 border-bottom pb-2">
                            <div class="col-sm-3">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                                        <i class="bx bx-camera"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-9">
                                <div class="mt-4 mt-sm-0">
                                    <h5 class="font-size-14 mb-1">{{ $item->packet->product->name ?? 'Unknown' }}</h5>
                                    <p class="text-muted mb-0 text-truncate">{{ $item->packet->name ?? '' }}</p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge bg-success font-size-12">{{ $item->total }} Terjual</span>
                                        <small class="text-muted">ID: #{{ $item->packet_id }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Advanced Recent Transactions Table --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Transaksi Terbaru</h4>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-primary btn-sm">Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i></a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Invoice</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th>Total Biaya</th>
                                    <th>Tanggal</th>
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
                                    
                                    $processStatusConfig = [
                                        'Pelanggan Belum Foto' => ['icon' => '📷❌', 'class' => 'bg-light text-dark'],
                                        'Pelanggan Pilih Foto' => ['icon' => '🖼️', 'class' => 'bg-info-subtle text-info-emphasis'],
                                        'Proses Edit' => ['icon' => '✏️', 'class' => 'bg-primary-subtle text-primary-emphasis'],
                                        'Proses Cetak' => ['icon' => '🖨️', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'Selesai' => ['icon' => '✅', 'class' => 'bg-success-subtle text-success-emphasis']
                                    ];
                                @endphp
                                @forelse($recent_transactions as $transaksi)
                                    <tr>
                                        <td><a href="javascript: void(0);" class="text-body fw-bold">{{ $transaksi->receipt_code }}</a></td>
                                        
                                        {{-- Pelanggan with Phone --}}
                                        <td>
                                            <h6 class="mb-1">{{ $transaksi->customer_name }}</h6>
                                            <div class="text-muted" style="font-size: 0.85em;">
                                                <i class="bx bx-phone text-secondary me-1"></i>
                                                {{ $transaksi->phone_number }}
                                            </div>
                                        </td>

                                        <td>
                                            <span class="fw-bold">{{ $transaksi->packet->name ?? 'N/A' }}</span>
                                            @if($transaksi->packet && $transaksi->packet->product)
                                                <br>
                                                <small class="text-muted">{{ $transaksi->packet->product->name }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-bold"><a href="javascript:void(0);" class="clickable-price" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</a></td>
                                        <td>{{ $transaksi->created_at->format('d M Y, H:i') }}</td>
                                        
                                        {{-- Advanced Payment Dropdown --}}
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST" class="status-update-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="field" value="status">
                                                <input type="hidden" name="redirect_to" value="dashboard">
                                                <select name="value" class="form-select form-select-sm payment-status-select {{ $paymentStatusConfig[$transaksi->status]['class'] ?? '' }}" data-transaction-id="{{ $transaksi->transaction_id }}">
                                                    @foreach ($paymentStatusConfig as $statusKey => $config)
                                                        <option value="{{ $statusKey }}" {{ $transaksi->status == $statusKey ? 'selected' : '' }}>
                                                            {{ $config['icon'] }} {{ $config['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>

                                        {{-- Advanced Process Dropdown --}}
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="field" value="process_status">
                                                <input type="hidden" name="redirect_to" value="dashboard">
                                                
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

                                                            // Logic Sequence Check
                                                            if ($loopIndex > $currentIndex) {
                                                                if ($loopIndex == $currentIndex + 1) {
                                                                    // OK
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

                                                            // Validation Logic
                                                            if ($status === 'Pelanggan Pilih Foto' && empty($transaksi->url_images)) {
                                                                $disabled = true; $labelSuffix = '(Link?)';
                                                            }
                                                            if ($status === 'Proses Cetak' && !$transaksi->hasPrintableItems()) {
                                                                $disabled = true; $labelSuffix = '(N/A)';
                                                            }
                                                            if ($status === 'Selesai') {
                                                                if ($transaksi->status !== 'sudah dibayar') {
                                                                    $disabled = true; $labelSuffix = '(Lunas?)';
                                                                } elseif (empty($transaksi->url_photos_result)) {
                                                                    $disabled = true; $labelSuffix = '(Final?)';
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
                                        
                                        {{-- Actions --}}
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-info update-url-btn" 
                                                        data-id="{{ $transaksi->transaction_id }}"
                                                        data-field="url_images"
                                                        data-value="{{ $transaksi->url_images }}"
                                                        data-bs-toggle="tooltip" title="Input Link Galeri">
                                                    <i class="mdi mdi-image-multiple"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-secondary update-url-btn"
                                                        data-id="{{ $transaksi->transaction_id }}"
                                                        data-field="url_photos_result"
                                                        data-value="{{ $transaksi->url_photos_result }}"
                                                        data-bs-toggle="tooltip" title="Input Link Final">
                                                    <i class="bx bx-check-double"></i>
                                                </button>

                                                @php
                                                    $canInputSelections = !empty($transaksi->url_images);
                                                    $existingText = "";
                                                    if($transaksi->select_edit_photo) $existingText .= "*DAFTAR FOTO EDIT*\n" . $transaksi->select_edit_photo . "\n\n";
                                                    if($transaksi->select_print_photo) $existingText .= "*DAFTAR FOTO CETAK*\n" . $transaksi->select_print_photo;
                                                @endphp
                                                <button type="button" class="btn btn-sm btn-warning input-selection-btn"
                                                        data-id="{{ $transaksi->transaction_id }}"
                                                        data-existing-text="{{ $existingText }}"
                                                        {{ !$canInputSelections ? 'disabled' : '' }}
                                                        data-bs-toggle="tooltip" title="Input Pilihan Foto">
                                                    <i class="bx bx-list-check"></i>
                                                </button>
                                                
                                                @if(!empty($transaksi->phone_number) && $transaksi->user)
                                                    @php
                                                        $hasPrint = $transaksi->hasPrintableItems();
                                                        $linkFinal = $transaksi->url_photos_result ? $transaksi->url_photos_result : "[Link Belum Diisi]";
                                                        $packetName = $transaksi->packet->name ?? 'N/A';
                                                        $productName = $transaksi->packet->product->name ?? 'N/A';
                                                        $backupNote = "Catatan Penting:\nMohon segera unduh dan backup foto Anda. Link drive akan kadaluarsa/dihapus dalam 14 hari.";
                                                        $detailPaket = "Detail Paket:\n*{$productName} - {$packetName}*";

                                                        if ($hasPrint) {
                                                            $pesanSelesai = "Halo Kak *{$transaksi->customer_name}*, kabar gembira! Foto Anda telah selesai dicetak & diedit.\n\n{$detailPaket}\n\nBerikut link softfile foto finalnya:\n{$linkFinal}\n\n{$backupNote}\n\nRincian pesanan atas:\nNama : {$transaksi->customer_name}\nNo. Nota : {$transaksi->receipt_code}\n\nSilakan ambil hasil cetak di studio kami. Terima kasih!\n\nJika kakak berkenan, boleh beri rating layanan kami di sini : https://g.page/r/CR-YHaNKJ2C_EBM/review";
                                                        } else {
                                                            $pesanSelesai = "Halo Kak *{$transaksi->customer_name}*, kabar gembira! Foto Anda telah selesai diedit.\n\n{$detailPaket}\n\nBerikut link softfile foto finalnya:\n{$linkFinal}\n\n{$backupNote}\n\nRincian pesanan atas:\nNama : {$transaksi->customer_name}\nNo. Nota : {$transaksi->receipt_code}\n\nTerima kasih telah mempercayakan momennya di Yunas Studio!\n\nJika kakak berkenan, boleh beri rating layanan kami di sini : https://g.page/r/CR-YHaNKJ2C_EBM/review";
                                                        }

                                                        $waMessages = [
                                                            'Pelanggan Belum Foto' => "Halo kak {$transaksi->customer_name}, jadwal foto belum terlaksana. Hubungi kami untuk info lebih lanjut.",
                                                            'Selesai' => $pesanSelesai
                                                        ];

                                                        if ($transaksi->process_status === 'Pelanggan Pilih Foto') {
                                                            $maxEdit = $transaksi->packet->max_photos_for_edit ?? 0;
                                                            $editList = "";
                                                            for ($i = 1; $i <= $maxEdit; $i++) { $editList .= "{$i}. \n"; }
                                                            $printList = "";
                                                            if ($transaksi->packet && $transaksi->packet->combined_defaults) {
                                                                foreach ($transaksi->packet->combined_defaults as $item) {
                                                                    if (stripos($item->name, 'cetak') !== false || stripos($item->name, 'print') !== false) {
                                                                        for ($q = 0; $q < $item->quantity; $q++) { $printList .= "- {$item->name} : \n"; }
                                                                    }
                                                                }
                                                            }
                                                            if (empty($printList)) { $printList = "- (Tidak ada item cetak) \n"; }
                                                            $linkGaleri = $transaksi->url_images ? $transaksi->url_images : "[Link Belum Diisi]";
                                                            $waMessages['Pelanggan Pilih Foto'] = "Halo kak *{$transaksi->customer_name}*, Terima kasih sudah mempercayakan momennya di Yunas Studio.\n\nDetail Paket:\n*{$productName} - {$packetName}*\n\nBerikut kami kirimkan link untuk pemilihan foto:\n{$linkGaleri}\n\n{$backupNote}\n\nMohon untuk mengisi format pemilihan foto dibawah ini:\n\n*DAFTAR FOTO EDIT (Max {$maxEdit} Foto)*\n{$editList}\n*DAFTAR FOTO CETAK*\n{$printList}\nTerima kasih";
                                                        }

                                                        $waLink = null;
                                                        if (isset($waMessages[$transaksi->process_status])) {
                                                            $waMessage = $waMessages[$transaksi->process_status];
                                                            $phoneNumber = preg_replace('/\D/', '', $transaksi->phone_number);
                                                            if (strpos($phoneNumber, '0') === 0) { $phoneNumber = '62' . substr($phoneNumber, 1); }
                                                            $waLink = "https://api.whatsapp.com/send?phone={$phoneNumber}&text=" . urlencode($waMessage);
                                                        }
                                                    @endphp
                                                    @if(isset($waLink))
                                                        <a href="{{ $waLink }}" target="_blank" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Kirim WA"><i class="uil uil-whatsapp"></i></a>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">Belum ada transaksi terbaru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    {{-- MODALS FOR TABLE ACTIONS --}}
    @foreach($recent_transactions as $transaksi)
        <div id="detailModal{{ $transaksi->transaction_id }}" class="modal fade invoice-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="invoice-content">
                            <div class="invoice-header text-center"><div class="mb-3"><img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="logo" class="invoice-logo"/></div><p class="text-muted mb-0">Jalan Lingkar Selatan, Sukabumi</p></div>
                            <div class="p-4">
                                <div class="row"><div class="col-md-6"><h5 class="font-size-16">Pelanggan:</h5><p class="mb-1">{{ $transaksi->customer_name }}</p><p class="mb-1 text-muted">{{ $transaksi->phone_number }}</p></div><div class="col-md-6 text-md-end"><h5 class="font-size-16">Info:</h5><p class="mb-1"><strong>Invoice:</strong> {{ $transaksi->receipt_code }}</p><p class="mb-1"><strong>Tgl:</strong> {{ $transaksi->created_at->format('d M Y, H:i') }}</p></div></div>
                                <div class="py-2 mt-3"><h3 class="font-size-15 fw-bold">Ringkasan</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap">
                                        <thead class="table-light"><tr><th>Item</th><th class="text-end">Harga</th></tr></thead>
                                        <tbody>
                                            @if($transaksi->packet)
                                                <tr>
                                                    <td>{{ $transaksi->packet->name }} <small class="text-muted">({{ $transaksi->packet->product->name ?? '' }})</small></td>
                                                    <td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                                                </tr>
                                            @endif
                                            @foreach($transaksi->additionals as $additional)
                                                <tr>
                                                    <td>{{ $additional->pivot->quantity }}x {{ $additional->name }}</td>
                                                    <td class="text-end">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="border-top"><td class="fw-bold">Total</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Shared Modals --}}
    <div class="modal fade" id="dpAmountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="dpAmountForm" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="field" value="status"><input type="hidden" name="value" value="dp"><input type="hidden" name="redirect_to" value="dashboard">
                    <div class="modal-header"><h5 class="modal-title">Masukkan Jumlah DP</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="number" class="form-control" id="dp_amount_modal" name="dp_amount" min="0" required placeholder="Rp 0"></div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="urlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="urlForm" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" id="url_field_input" name="field" value=""><input type="hidden" name="redirect_to" value="dashboard">
                    <div class="modal-header"><h5 class="modal-title" id="urlModalLabel">Update Link</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="url" class="form-control" id="url_input" name="value" placeholder="https://..."></div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inputSelectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="selectionForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">Input Pilihan Foto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><textarea class="form-control" id="selection_text_input" name="selection_text" rows="10" placeholder="Paste pesan WA pelanggan di sini..."></textarea></div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- INCOME FILTER LOGIC ---
            const incomeFilters = document.querySelectorAll('.income-filter');
            const incomeDisplay = document.getElementById('total-income-display');
            const incomeSortLabel = document.getElementById('income-sort-label');
            const incomeSubtext = document.getElementById('income-subtext');

            incomeFilters.forEach(filter => {
                filter.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const type = this.dataset.type;
                    const totalVal = parseFloat(incomeDisplay.dataset.total);
                    const monthlyVal = parseFloat(incomeDisplay.dataset.monthly);
                    
                    let displayVal = 0;
                    let labelText = "Total";
                    let subtextHtml = "";

                    if (type === 'monthly') {
                        displayVal = monthlyVal;
                        labelText = "Bulan Ini";
                        subtextHtml = '<span class="text-info me-1"><i class="mdi mdi-calendar-month me-1"></i></span> Bulan Ini';
                    } else {
                        displayVal = totalVal;
                        labelText = "Total";
                        subtextHtml = '<span class="text-success me-1"><i class="mdi mdi-chart-line me-1"></i></span> Akumulasi';
                    }

                    // Format Number (ID-ID)
                    const formatter = new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });

                    incomeDisplay.textContent = formatter.format(displayVal);
                    incomeSortLabel.textContent = labelText;
                    incomeSubtext.innerHTML = subtextHtml;
                });
            });


            // --- CHART LOGIC ---
            // Data passed from controller
            const chartData = {
                daily: { labels: @json($chart_daily_labels), data: @json($chart_daily_data) },
                monthly: { labels: @json($chart_monthly_labels), data: @json($chart_monthly_data) },
                yearly: { labels: @json($chart_yearly_labels), data: @json($chart_yearly_data) }
            };

            const chartElement = document.querySelector("#sales-analytics-chart");
            let currentChart = null;

            function renderChart(type) {
                const data = chartData[type];
                
                const options = {
                    series: [{ name: 'Pemasukan', data: data.data }],
                    chart: { 
                        height: 450, 
                        type: 'area', 
                        toolbar: { show: false },
                        zoom: { enabled: false }
                    },
                    colors: ['#556ee6'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, inverseColors: false, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100, 100, 100] }
                    },
                    xaxis: { categories: data.labels },
                    yaxis: { 
                        labels: { 
                            formatter: function (value) { return "Rp " + new Intl.NumberFormat('id-ID').format(value); } 
                        } 
                    },
                    grid: { borderColor: '#f1f1f1' }
                };

                if (currentChart) {
                    currentChart.destroy();
                }
                currentChart = new ApexCharts(chartElement, options);
                currentChart.render();
            }

            // Initial Render (Daily)
            renderChart('daily');

            // Handle Filter Clicks
            document.querySelectorAll('.chart-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.chart-filter').forEach(b => b.classList.remove('active', 'btn-primary'));
                    document.querySelectorAll('.chart-filter').forEach(b => b.classList.add('btn-light'));
                    
                    this.classList.remove('btn-light');
                    this.classList.add('active', 'btn-primary');
                    
                    renderChart(this.dataset.filter);
                });
            });


            // --- ADVANCED TABLE LOGIC ---
            
            // DP Modal
            const dpModal = new bootstrap.Modal(document.getElementById('dpAmountModal'));
            const dpForm = document.getElementById('dpAmountForm');
            document.querySelectorAll('.payment-status-select').forEach(select => {
                select.addEventListener('change', function (e) {
                    if (e.target.value === 'dp') {
                        dpForm.action = e.target.closest('form').action;
                        dpModal.show();
                    } else {
                        e.target.closest('form').submit();
                    }
                });
            });

            // URL Modal
            const urlModal = new bootstrap.Modal(document.getElementById('urlModal'));
            const urlForm = document.getElementById('urlForm');
            const urlInput = document.getElementById('url_input');
            const urlLabel = document.getElementById('urlModalLabel');
            const urlFieldInput = document.getElementById('url_field_input');

            document.querySelectorAll('.update-url-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const field = this.dataset.field;
                    const val = this.dataset.value;
                    
                    if (field === 'url_images') urlLabel.textContent = 'Update Link Galeri';
                    else urlLabel.textContent = 'Update Link Final';

                    urlInput.value = val;
                    urlFieldInput.value = field;
                    urlForm.action = `/transaksi/${id}/update-status`;
                    urlModal.show();
                });
            });

            // Selection Modal
            const selModal = new bootstrap.Modal(document.getElementById('inputSelectionModal'));
            document.querySelectorAll('.input-selection-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('selection_text_input').value = this.dataset.existingText;
                    document.getElementById('selectionForm').action = `/transaksi/${this.dataset.id}/update-selections`;
                    selModal.show();
                });
            });

            // Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
        });
    </script>
@endsection