<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel S3 Upload Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">S3 Image Upload</h2>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Success!</strong> {{ session('success') }}
            </div>

            <div class="mb-6 text-center">
                <p class="text-sm text-gray-600 mb-2">Uploaded Image Preview:</p>
                <div class="border rounded-lg p-2 bg-gray-50 inline-block">
                    <img src="{{ session('url') }}" alt="S3 Uploaded Image" class="max-w-full h-auto rounded shadow-sm" style="max-height: 300px;">
                </div>
                <div class="mt-2">
                    <a href="{{ session('url') }}" target="_blank" class="text-blue-500 hover:underline text-xs break-all">
                        Open Original S3 Link
                    </a>
                </div>
            </div>
        @endif

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Upload Form --}}
        <form action="{{ route('upload.images') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label for="image" class="block text-gray-700 text-sm font-bold mb-2">Select Image</label>
                <input id="image" type="file" name="image" 
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-lg p-2"
                    required>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                Upload to Amazon S3
            </button>
        </form>
    </div>

</body>
</html>
