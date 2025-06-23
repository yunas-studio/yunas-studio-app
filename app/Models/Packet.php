<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Packet extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'price',
        'product_id',
        'image',
        'is_active'
    ];

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
    
    /**
     * Get the product that owns the packet.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the default additionals for this packet.
     */
    public function additionalDefaults(): HasMany
    {
        return $this->hasMany(AdditionalDefault::class);
    }

    /**
     * Get all the additional items included with this packet.
     */
    public function additionals()
    {
        return $this->hasManyThrough(
            Additional::class,
            AdditionalDefault::class,
            'packet_id', // Foreign key on AdditionalDefault table
            'id', // Foreign key on Additional table
            'id', // Local key on Packet table
            'additional_id' // Local key on AdditionalDefault table
        );
    }
}
