<?php

namespace App\Http\Controllers\AWS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Event\Events;

class S3UploadController extends Controller
{
   // Show upload form
    public function showForm()
    {
        return view('testuploads');
    }

    // Handle image upload
    
   public function uploadImages(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        if (!$request->hasFile('image')) {
            return back()->withErrors(['image' => 'No file uploaded']);
        }

        $file = $request->file('image');

        // 1. Store the file (Standard upload)
        $path = $file->store('uploads', 's3');

        if (!$path) {
            return back()->withErrors(['image' => 'Failed to upload file to S3']);
        }

        // 2. Generate a SECURE temporary URL (Valid for 5 minutes)
        // This works even if your bucket is set to "Block all public access"
        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));

        return back()
            ->with('success', 'Image uploaded successfully!')
            ->with('url', $url);
    }


    //save event
  
}
