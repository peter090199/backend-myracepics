<?php

namespace App\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventImage extends Model
{
     protected $table = 'event_images'; // Make sure your table name matches
     protected $fillable = [
        'code',
        'role_code',
        'fullname',
        'evnt_id',
        'evnt_name',
        'img_price',
        'img_qty',
        'platform_fee',
        'service_fee',
    ];

    /**
     * Relation to details (images)
     */
    public function details()
    {
        return $this->hasMany(ImagesUpload::class, 'event_image_id');
    }
}
