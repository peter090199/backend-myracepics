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
     public function save(Request $request)
    {
        // 1. Validation: 'image' is now a file validation
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date'     => 'required|date',
            'category' => 'required|string|max:100',
            'image'    => 'required|image|max:5120', // Max 5MB
        ]);

        $user = Auth::user();
        $code = $user->code;
        $roleCode = $user->role_code;
        $storedPath = null;

        // 2. Handle File Upload (Standard S3 Upload)
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');

                // Construct your specific directory structure
                // Example: "ADMIN/USER123/events"
                $directory = trim("{$roleCode}/{$code}/events", '/');

                // Store the file with a random name in that directory
                $storedPath = $file->store($directory, 's3');

                if (!$storedPath) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload file to S3'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWS S3 Error: ' . $e->getMessage()
                ], 500);
            }
        }

        // 3. Generate unique Event ID
        do {
            $eventId = strtoupper(Str::random(10));
        } while (Events::where('evnt_id', $eventId)->exists());

        // 4. Save event to database
        try {
            $event = Events::create([
                'title'     => $validated['title'],
                'location'  => $validated['location'],
                'date'      => $validated['date'],
                'category'  => $validated['category'],
                'code'      => $code,
                'role_code' => $roleCode,
                'evnt_id'   => $eventId,
                // Store the path as a JSON array as per your original structure
                'image'     => json_encode($storedPath ? [$storedPath] : []),
            ]);

            // Generate a temporary URL just for the immediate response preview
            $temporaryUrl = $storedPath ? Storage::disk('s3')->temporaryUrl($storedPath, now()->addMinutes(5)) : null;

            return response()->json([
                'success' => true,
                'message' => 'Successfully saved.',
                'event'   => $event,
                'url'     => $temporaryUrl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
