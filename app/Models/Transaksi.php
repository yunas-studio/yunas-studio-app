<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'customer_name',
        'status',
        'receipt_code',
        'temporary_link',
        'selected_photos',
        'final_link',
        // 'transaction_date', // Removed
        'isActive'
    ];

    /**
     * Get the booking associated with the transaction.
     * A transaction has one booking.
     */
    public function booking()
    {
        return $this->hasOne(Booking::class, 'transaction_id', 'transaction_id');
    }

    /**
     * The products that belong to the transaction.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'orders', 'transaction_id', 'product_id')
                    ->withTimestamps();
    }
}
