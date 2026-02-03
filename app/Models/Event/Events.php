<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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


}
