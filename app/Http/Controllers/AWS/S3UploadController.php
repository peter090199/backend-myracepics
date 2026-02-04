<?php

namespace App\Http\Controllers\AWS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class S3UploadController extends Controller
{
    /**
     * Show the upload form
     */
    public function showForm()
    {
        return view('testuploads'); // make sure this view exists
    }

    /**
     * Handle image upload to S3
     */
    public function uploadImages(Request $request)
    {
        // Validate uploaded file (max 10MB)
        $request->validate([
            'image' => 'required|image|max:10240', // 10240 KB = 10 MB
        ]);

        if (!$request->hasFile('image')) {
            return back()->withErrors(['image' => 'No file uploaded']);
        }

        $file = $request->file('image');

        try {
            // Generate a unique filename
            $filename = time() . '_' . $file->getClientOriginalName();

            // Store the file on S3 (uploads folder)
            $path = $file->storeAs('uploads', $filename, 's3');

            if (!$path) {
                return back()->withErrors(['image' => 'Failed to upload file to S3']);
            }

            // Generate a temporary secure URL (5 minutes)
            $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));

            return back()
                ->with('success', 'Image uploaded successfully!')
                ->with('url', $url);

        } catch (\Exception $e) {
            return back()->withErrors([
                'image' => 'S3 upload error: ' . $e->getMessage()
            ]);
        }
    }

    
}
