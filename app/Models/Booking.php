<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'transaction_id',
        'booking_datetime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'booking_datetime' => 'datetime',
    ];

    /**
     * Get the transaction that owns the booking.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaksi::class, 'transaction_id', 'transaction_id');
    }
}
    