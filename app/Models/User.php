<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'id',
        'username',
        'fname',
        'lname',
        'mname',
        'contactno',
        'fullname',
        'email',
        'password',
        'status',
        'company',
        'code',
        'role_code',
        'is_online',
        'coverphoto',
        'role',
        'google_id',
        'google_token',
        'google_refresh_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'code',
        'role_code',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_online' => 'boolean',
    ];

    /**
     * Save or update the user's cover photo
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The public URL of the saved cover photo
     * @throws \Exception
     */
    public function saveCoverPhoto($file): string
    {
        if (!$file) {
            throw new \Exception("No file provided");
        }

        // Delete old cover photo if exists
        if ($this->coverphoto) {
            $oldPath = str_replace(Storage::url(''), '', $this->coverphoto);
            if (Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }
        }

        $uuid = Str::uuid();
        $folderPath = "uploads/{$this->id}/cover_photo/{$uuid}";
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($folderPath, $fileName, 'public');

        // Use Storage::url() to get proper public URL
        $this->coverphoto = Storage::url($filePath);
        $this->save();

        return $this->coverphoto;
    }
}
