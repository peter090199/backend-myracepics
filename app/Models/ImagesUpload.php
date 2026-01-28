<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ImagesUpload extends Model
{
    protected $table = 'images_uploads'; 
    protected $fillable = [
        'event_image_id', // FK to header
        'code',
        'role_code',
        'fullname',
        'evnt_id',
        'evnt_name',
        'img_id',
        'img_name',
        'original_path',
        'watermark_path',
        'img_price',
        'img_qty',
        'platform_fee',
        'service_fee',
    ];

    /**
     * Relation to header
     */
    public function header()
    {
        return $this->belongsTo(EventImage::class, 'event_image_id');
    }
}
