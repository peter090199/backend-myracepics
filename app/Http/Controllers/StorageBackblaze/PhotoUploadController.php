<?php

namespace App\Http\Controllers\StorageBackblaze;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoUploadController extends Controller
{
    public function upload(Request $request)
    {
        try {
            // Validate the uploaded file
            $request->validate([
                'file' => 'required|file|max:10240', // max 10MB
            ]);

            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName(); // unique filename

            // Save to Backblaze B2 in 'public/photos' folder
            $path = Storage::disk('b2')->putFileAs('public/photos', $file, $filename);

            // Get public URL
            $url = Storage::disk('b2')->url($path);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => $url
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ], 500);
        }
    }


    // Optional: list all files in the bucket
    public function list()
    {
        try {
            // List all files in 'public/photos' folder
            $files = Storage::disk('b2')->allFiles('public/photos');

            // Generate public URLs
            $urls = array_map(fn($file) => Storage::disk('b2')->url($file), $files);

            return response()->json([
                'success' => true,
                'files' => $urls
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error listing files: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $path = $request->input('path');

        try {
            if (!Storage::disk('b2')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist'
                ], 404);
            }

            Storage::disk('b2')->delete($path);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

}
