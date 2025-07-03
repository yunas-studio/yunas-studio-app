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
        'user_id',
        'customer_name',
        'phone_number',
        'packet_id',
        'status',
        'process_status',
        'receipt_code',
        'temporary_link',
        'selected_photos',
        'final_link',
        // 'transaction_date', // Removed
        'total_price',
        'discount',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The packets that belong to the transaction.
     */
    public function packet()
    {
        return $this->belongsTo(Packet::class);
    }

    public function additionals()
    {
        return $this->belongsToMany(Additional::class, 'additional_transaksi', 'transaksi_id', 'additional_id')
                    ->withPivot('quantity', 'price')
                    ->withTimestamps();
    }
}
