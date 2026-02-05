<?php

namespace App\Http\Controllers\AWS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class S3UploadController extends Controller
{

    public function s3upload(Request $request){
        $path = $request->file('file')->store('public/images');
        return response()->json([
            'path'=>$path,
            'msg'=>'success',
        ]);
    }



        /**
     * Show the upload form
     */
    // public function showForm()
    // {
    //     return view('testuploads'); // make sure this view exists
    // }

    // /**
    //  * Handle image upload to S3
    //  */
    // public function uploadImages(Request $request)
    // {
    //     $request->validate([
    //         'image' => 'required|image|max:10240', // 10 MB
    //     ]);

    //     if (!$request->hasFile('image')) {
    //         return back()->withErrors(['image' => 'No file uploaded']);
    //     }

    //     $file = $request->file('image');
    //     $filename = time() . '_' . $file->getClientOriginalName();

    //     try {
    //         // Upload to S3
    //         $path = Storage::disk('s3')->putFileAs('uploads', $file, $filename);

    //         // Generate temporary URL
    //         $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));

    //         return back()
    //             ->with('success', 'Image uploaded successfully!')
    //             ->with('url', $url);

    //     } catch (\Exception $e) {
    //         return back()->withErrors(['image' => 'S3 upload error: ' . $e->getMessage()]);
    //     }
    // }
    
}
