<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Events extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'events'; 
    public $timestamps = true;

    protected $fillable = [
        'title',
        'location',
        'date',
        'category',
        'image',
        'evnt_id',
        'code',
        'role_code'
    ];

    /**
     * Boot method to auto-generate UUID on creating event
     */
    protected static function booted()
    {
        static::creating(function ($event) {
        if (empty($event->evnt_id)) {
            $event->evnt_id = 'EVNT-' . strtoupper(Str::random(10));
        }
      });
   }
}
