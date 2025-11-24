@extends('layouts.app')

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
                    @if($transaction->photos && $transaction->photos->count() > 0)
                        <div class="mb-4">
                            <h5>Foto yang Sudah Diupload:</h5>
                            <div class="list-group">
                                @foreach($transaction->photos as $photo)
                                    <a href="{{ $photo->url_images }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="fas fa-image me-2"></i>
                                        {{-- Menampilkan nama file dari URL --}}
                                        {{ basename(parse_url($photo->url_images, PHP_URL_PATH)) }}
                                        <i class="fas fa-external-link-alt ms-auto text-muted small"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                    @endif

                    <p class="card-text">Silakan pilih foto yang ingin Anda sertakan dalam paket ini.</p>

                    {{-- Ganti 'transaction.photos.upload' dengan nama route yang sesuai --}}
                    <form action="{{ route('transaction.photos.upload', ['transaction' => $transaction->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="photos" class="form-label">Upload File Foto Baru</label>
                            <input
                                type="file"
                                class="form-control @error('photos.*') is-invalid @enderror @error('photos') is-invalid @enderror"
                                id="photos"
                                name="photos[]"
                                multiple
                                accept="image/jpeg,image/png,image/jpg"
                            >
                            <div class="form-text">Anda dapat memilih lebih dari satu file. Format yang didukung: JPG, PNG.</div>

                            @error('photos')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('photos.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Bagian ini akan tampil jika transaksi memiliki opsi cetak tambahan --}}
                        {{-- Ganti 'has_additional_print' dengan kondisi yang sesuai dari data transaksi Anda --}}
                        @if($transaction->has_additional_print)
                            <div class="mb-3">
                                <label for="edit_photo_selection" class="form-label">Pilihan Foto untuk Diedit</label>
                                <textarea class="form-control" id="edit_photo_selection" name="edit_photo_selection" rows="3" placeholder="Tuliskan nama file foto yang ingin diedit, pisahkan dengan koma. Contoh: IMG_001.jpg, IMG_002.jpg">{{ old('edit_photo_selection', $transaction->edit_photo_selection) }}</textarea>
                                <div class="form-text">Isi jika ada permintaan edit foto tambahan.</div>
                            </div>

                            <div class="mb-3">
                                <label for="print_photo_selection" class="form-label">Pilihan Foto untuk Dicetak</label>
                                <textarea class="form-control" id="print_photo_selection" name="print_photo_selection" rows="3" placeholder="Tuliskan nama file foto yang ingin dicetak, pisahkan dengan koma. Contoh: IMG_003.jpg, IMG_004.jpg">{{ old('print_photo_selection', $transaction->print_photo_selection) }}</textarea>
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