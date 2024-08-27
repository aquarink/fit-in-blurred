<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$no_image_path = 'no_image.jpg'; // Path ke file no_image.jpg

// Validasi input parameter
$errors = [];
$source_url = $_GET['source'] ?? null;
$target_width = $_GET['width'] ?? null;
$target_height = $_GET['height'] ?? null;

if (!$source_url) {
    $errors[] = 'Source URL is required';
}
if (!$target_width) {
    $errors[] = 'Target width is required';
}
if (!$target_height) {
    $errors[] = 'Target height is required';
} else {
    if (!is_numeric($target_width) || $target_width <= 0) {
        $errors[] = 'Target width must be a positive number';
    }
    if (!is_numeric($target_height) || $target_height <= 0) {
        $errors[] = 'Target height must be a positive number';
    }
}

if (count($errors) > 0) {
    // Proses no_image.jpg jika ada error pada input
    processAndOutputFallbackImage($no_image_path, (int)$target_width, (int)$target_height);
    exit;
}

// Validasi URL sumber
if (!filter_var($source_url, FILTER_VALIDATE_URL)) {
    processAndOutputFallbackImage($no_image_path, (int)$target_width, (int)$target_height);
    exit;
}

// Ambil gambar dari URL sumber
$image_data = get_image_data($source_url);
if ($image_data === FALSE) {
    processAndOutputFallbackImage($no_image_path, (int)$target_width, (int)$target_height);
    exit;
}

// Buat gambar dari data yang diperoleh
$image = @imagecreatefromstring($image_data);
if ($image === FALSE) {
    processAndOutputFallbackImage($no_image_path, (int)$target_width, (int)$target_height);
    exit;
}

// Proses gambar dan langsung tampilkan
$processed_image = processImage($image, (int)$target_width, (int)$target_height);
header('Content-Type: image/jpeg');
echo $processed_image;

// Fungsi untuk memproses fallback image
function processAndOutputFallbackImage($image_path, $target_width, $target_height) {
    $image = imagecreatefromjpeg($image_path);
    $processed_image = processImage($image, $target_width, $target_height);
    header('Content-Type: image/jpeg');
    echo $processed_image;
    imagedestroy($image);
}

// Fungsi untuk mendapatkan data gambar dari URL
function get_image_data($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);

    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if (strpos($content_type, 'image') === false) {
        curl_close($ch);
        return false;
    }

    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Fungsi untuk memproses gambar
function processImage($image, $target_width, $target_height) {
    $width = imagesx($image);
    $height = imagesy($image);

    $scaling_factor = min($target_width / $width, $target_height / $height);

    $new_width = $width * $scaling_factor;
    $new_height = $height * $scaling_factor;

    $canvas = imagecreatetruecolor($target_width, $target_height);

    $background = imagecreatetruecolor($target_width, $target_height);
    imagecopyresampled($background, $image, 0, 0, 0, 0, $target_width, $target_height, $width, $height);
    for ($i = 0; $i < 50; $i++) {
        imagefilter($background, IMG_FILTER_GAUSSIAN_BLUR);
    }

    imagecopy($canvas, $background, 0, 0, 0, 0, $target_width, $target_height);
    imagecopyresampled($canvas, $image, ($target_width - $new_width) / 2, ($target_height - $new_height) / 2, 0, 0, $new_width, $new_height, $width, $height);

    ob_start();
    imagejpeg($canvas, null, 90);
    $output = ob_get_clean();

    imagedestroy($image);
    imagedestroy($canvas);
    imagedestroy($background);

    return $output;
}
?>