@extends('layouts.master')
@section('title', 'Photo Gallery')
@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Pilih Foto untuk Transaksi</h4>
                </div>
                <div class="card-body">
                    {{-- Menampilkan foto yang sudah diupload --}}
                    <div class="mb-4">
                        <h5>Foto yang Sudah Diupload:</h5>
                        <div>
                            <a href="{{ $transaksi->url_images }}" target="_blank">
                                <i class="fas fa-image me-2"></i>
                                {{ $transaksi->url_images }}
                                <i class="fas fa-external-link-alt ms-auto text-muted small"></i>
                            </a>
                        </div>
                    </div>
                    <hr>

                    <p class="card-text">Silakan pilih foto yang ingin Anda sertakan dalam paket ini.</p>

                    {{-- Form untuk upload foto baru dan memilih foto untuk diedit/dicetak --}}
                    <form action="{{ route('transaksi.handle-select-for-edit', ['transaksi' => $transaksi->transaction_id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="select_edit_photo" class="form-label">Pilihan Foto untuk Diedit</label>
                            <textarea class="form-control" id="select_edit_photo" name="select_edit_photo" rows="3" placeholder="Tuliskan nama file foto yang ingin diedit, pisahkan dengan koma. Contoh: IMG_001.jpg, IMG_002.jpg">{{ old('select_edit_photo', $transaksi->select_edit_photo) }}</textarea>
                            <div class="form-text">Isi jika ada permintaan edit foto tambahan.</div>
                        </div>
                        {{-- Tampilkan field ini jika transaksi memiliki opsi cetak/edit tambahan --}}
                        @if($transaksi->hasPrintableItems())
                            <div class="mb-3">
                                <label for="select_print_photo" class="form-label">Pilihan Foto untuk Dicetak</label>
                                <textarea class="form-control" id="select_print_photo" name="select_print_photo" rows="3" placeholder="Tuliskan nama file foto yang ingin dicetak, pisahkan dengan koma. Contoh: IMG_003.jpg, IMG_004.jpg">{{ old('select_print_photo', $transaksi->select_print_photo) }}</textarea>
                                <div class="form-text">Isi jika ada permintaan cetak foto tambahan.</div>
                            </div>
                        @endif


                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Simpan Perubahan <i class="fas fa-save ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
{{-- Pastikan Font Awesome sudah di-load di layout utama untuk menampilkan ikon --}}
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> --}}
@endpush
