<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hover Preview Watermark</title>
    <style>
        .image-preview-container {
            position: relative;
            display: inline-block;
            margin: 20px;
        }

        .thumbnail {
            width: 200px; /* thumbnail size */
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Full preview */
        .preview {
            position: absolute;
            top: 0;
            left: 220px; /* show right of thumbnail */
            width: auto;
            max-width: 400px;
            max-height: 400px;
            display: none;
            border: 2px solid #333;
            border-radius: 6px;
            background: #fff;
            z-index: 10;
        }

        /* Show preview on hover */
        .image-preview-container:hover .preview {
            display: block;
        }
    </style>
</head>
<body>
    <!-- <h1>Hover to preview watermarked image</h1>

    <div class="image-preview-container">
        {{-- Original thumbnail --}}
        <img src="{{ asset('storage/app/public/profile.jpg') }}" alt="Original" class="thumbnail">

        {{-- Watermarked preview --}}
        <img src="{{ asset('storage/app/public/diagonal-watermarked.jpg') }}" alt="Watermarked" class="preview">
    </div> -->

    <h1>Hover to preview watermarked image</h1>

<div class="image-preview-container">
    <img src="{{ asset('storage/app/public/PROFILE.jpg') }}" alt="Original" class="thumbnail">

    <img src="{{ asset('storage/diagonal-watermarked.jpg') }}" alt="Watermarked" class="preview">
</div>

</body>
</html>
