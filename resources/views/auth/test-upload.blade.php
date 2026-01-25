<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Images with Watermark</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f2f4f7; padding: 20px; }
        .google-btn { display: inline-flex; align-items: center; gap: 10px; background: #4285F4; color: #fff; padding: 12px; border-radius: 4px; text-decoration: none; font-weight: bold; cursor: pointer; }
        .gallery img { max-width: 200px; margin: 5px; border: 1px solid #ccc; }
        pre { background: #eee; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>

<h2>Upload Images with Watermark</h2>

<input type="file" id="photos" multiple accept="image/*"><br><br>

<label>
    <input type="checkbox" id="apply_watermark" checked> Apply Watermark
</label><br><br>

<button id="uploadBtn">Upload & Display</button>

<h3>Gallery</h3>
<div id="gallery" class="gallery"></div>

<h3>Server Response</h3>
<pre id="response"></pre>

<script>
const API_URL = "http://127.0.0.1:8000/api/upload"; // Your API route
const TOKEN = "2217|C6otzG2zeiAB3lFrla64CLhk3gEHyTSthXdQtoqP6591ec10"; // Replace with your actual token

document.getElementById('uploadBtn').addEventListener('click', upload);

function upload() {
    const files = document.getElementById('photos').files;
    const applyWatermark = document.getElementById('apply_watermark').checked;

    if (!files.length) return alert('Select at least one photo');

    const formData = new FormData();
    Array.from(files).forEach(file => formData.append('photos[]', file));
    formData.append('apply_watermark', applyWatermark);

    fetch(API_URL, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + TOKEN
            // Do NOT set Content-Type manually when using FormData
        },
        body: formData
    })
    .then(res => res.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
            document.getElementById('response').textContent = JSON.stringify(data, null, 2);
        } catch(e) {
            console.error('Server returned HTML:', text);
            document.getElementById('response').textContent = "Server returned HTML (likely authentication error or 500):\n\n" + text;
            return;
        }

        // Display uploaded images if successful
        if (data.success && data.files) {
            const gallery = document.getElementById('gallery');
            gallery.innerHTML = '';
            data.files.forEach(file => {
                const img = document.createElement('img');
                // Adjust URL to match your storage path
                img.src = `http://127.0.0.1:8000/storage/uploads/${file}`;
                gallery.appendChild(img);
            });
        }
    })
    .catch(err => document.getElementById('response').textContent = err);
}
</script>

</body>
</html>
