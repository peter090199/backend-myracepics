<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageCart extends Model
{
    use HasFactory;
    protected $table = 'image_carts'; // Ensure this matches your migration

    protected $fillable = [
        'img_id',
        'img_name',
        'img_price',
        'img_qty',
        'watermark_url',
        'platform_fee',
        'service_fee',
        'status',
        'code',
        'role_code',
        'fullname',
        'evnt_id',
        'evnt_name',
    ];

    // Casting ensures numbers stay numbers in JSON responses
    protected $casts = [
        'img_price' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'img_qty' => 'integer',
    ];
    
}
