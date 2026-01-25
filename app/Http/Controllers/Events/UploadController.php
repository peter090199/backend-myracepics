<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class UploadController extends Controller
{

    public function multipleUpload(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'required|string',
            'apply_watermark' => 'boolean'
        ]);

        $applyWatermark = $request->boolean('apply_watermark', true);

        $manager = ImageManager::gd(); // ✅ Intervention v3
        $folderId = Str::uuid()->toString();

        $uploaded = [];

        foreach ($request->photos as $index => $photo) {

            // ✅ Detect & clean base64
            if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                $photo = substr($photo, strpos($photo, ',') + 1);
                $ext = strtolower($type[1]);
            } else {
                $ext = 'png'; // default
            }

            $decoded = base64_decode(str_replace(' ', '+', $photo), true);
            if ($decoded === false) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid Base64 at index $index"
                ], 400);
            }

            $fileName = "photo-$index-$folderId.$ext";

            $originalPath = "uploads/$folderId/original/$fileName";
            $watermarkedPath = "uploads/$folderId/watermarked/$fileName";

            // ✅ Save original
            Storage::disk('public')->put($originalPath, $decoded);

            // ✅ Watermark logic
            if ($applyWatermark) {
                $image = $manager->read($decoded);

                $watermarkPath = public_path('images/watermark.jpg');
                if (file_exists($watermarkPath)) {
                    $image->place(
                        $watermarkPath,
                        'bottom-right',
                        10,
                        10,
                        25 // opacity
                    );
                }

                Storage::disk('public')->put(
                    $watermarkedPath,
                    (string) $image->encode($ext, 90)
                );
            } else {
                Storage::disk('public')->put($watermarkedPath, $decoded);
            }

            $uploaded[] = [
                'original' => asset("storage/$originalPath"),
                'watermarked' => asset("storage/$watermarkedPath"),
            ];
        }

        return response()->json([
            'success' => true,
            'files' => $uploaded
        ]);
    }

    
    public function multipleUploadxx(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $code = $user->code;
        $roleCode = $user->role_code;

        if (!$request->has('photos') || !is_array($request->input('photos'))) {
            return response()->json(['success' => false, 'message' => 'Photos array is required'], 400);
        }

        $applyWatermark = $request->input('apply_watermark', true);
        $uploadedFiles = [];
        $folderId = Str::uuid()->toString();

        foreach ($request->input('photos') as $index => $photoBase64) {
            // Remove prefix if exists
            if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $type)) {
                $imageData = substr($photoBase64, strpos($photoBase64, ',') + 1);
                $extension = strtolower($type[1]);
            } else {
                $imageData = $photoBase64; // raw Base64
                $extension = 'png'; // default
            }

            // Clean Base64: remove spaces and line breaks
            $imageData = str_replace(["\r", "\n", ' '], '', $imageData);

            // Decode
            $decoded = base64_decode($imageData, true);

            if ($decoded === false) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid Base64 at index $index",
                    'example_fix' => 'Make sure the Base64 string is complete, has no line breaks, or use data:image/...;base64, prefix'
                ], 400);
            }

            $fileName = 'photo-' . time() . '-' . $index . '.' . $extension;
            $originalPath = "$roleCode/$code/events/$folderId/original/$fileName";
            $watermarkedPath = "$roleCode/$code/events/$folderId/watermarked/$fileName";

            // Create directories if not exist
            foreach ([$originalPath, $watermarkedPath] as $path) {
                $dir = storage_path('app/public/' . dirname($path));
                if (!is_dir($dir)) mkdir($dir, 0755, true);
            }

            // Save original
            Storage::disk('public')->put($originalPath, $decoded);

            // Watermark
            if ($applyWatermark) {
                try {
                    $image = Image::make($decoded)->orientate();
                     $watermarkPath = storage_path('app/public/watermark.jpg'); // server file path
                    if (file_exists($watermarkPath)) {
                        $watermark = Image::make($watermarkPath)
                            ->resize(150, null, fn($c) => $c->aspectRatio()->upsize())
                            ->opacity(60);
                        $image->insert($watermark, 'bottom-right', 15, 15);
                    }

                    Storage::disk('public')->put($watermarkedPath, (string)$image->encode($extension, 90));
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "Watermark failed at photo #$index",
                        'error' => $e->getMessage()
                    ], 500);
                }
            } else {
                Storage::disk('public')->put($watermarkedPath, $decoded);
            }

            $uploadedFiles[] = [
                'name' => $fileName,
                'original' => asset('storage/' . $originalPath),
                'watermarked' => asset('storage/' . $watermarkedPath),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Photos uploaded successfully',
            'files' => $uploadedFiles
        ]);
    }

    public function uploadFiles(Request $request)
    {
        $request->validate([
            'photos.*' => 'required|image|max:10240',
            'apply_watermark' => 'boolean'
        ]);

        $applyWatermark = $request->input('apply_watermark', false);
        $uploadedFiles = [];
        $user = Auth::user();
        $code = $user->code;
        $roleCode = $user->role_code;
        $folderId = Str::uuid()->toString();

        foreach ($request->file('photos') as $index => $file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $originalPath = "$roleCode/$code/events/$folderId/original/$fileName";
            $watermarkedPath = "$roleCode/$code/events/$folderId/watermarked/$fileName";

            foreach ([$originalPath, $watermarkedPath] as $path) {
                $dir = storage_path('app/public/' . dirname($path));
                if (!is_dir($dir)) mkdir($dir, 0755, true);
            }

            $file->storeAs('public/' . dirname($originalPath), basename($originalPath));

            if ($applyWatermark) {
                try {
                    $image = Image::make($file->getRealPath());
                    $watermarkPath = storage_path('app/public/watermark.jpg');
                    if (file_exists($watermarkPath)) {
                        $watermark = Image::make($watermarkPath)
                            ->resize(150, null, fn($c) => $c->aspectRatio()->upsize())
                            ->opacity(60);
                        $image->insert($watermark, 'bottom-right', 15, 15);
                    }
                    Storage::disk('public')->put($watermarkedPath, (string)$image->encode());
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "Watermark failed at file #$index",
                        'error' => $e->getMessage()
                    ], 500);
                }
            } else {
                Storage::disk('public')->put($watermarkedPath, file_get_contents($file->getRealPath()));
            }

            $uploadedFiles[] = [
                'name' => $fileName,
                'original' => asset('storage/' . $originalPath),
                'watermarked' => asset('storage/' . $watermarkedPath),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles
        ]);
    }
}
