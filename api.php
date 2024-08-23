<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    
    $target_width = isset($_POST['target_width']) ? (int)$_POST['target_width'] : 200;
    $target_height = isset($_POST['target_height']) ? (int)$_POST['target_height'] : 300;

    // Simpan gambar yang diunggah
    $upload_dir = 'files_upload/';
    $filename = basename($_FILES['image']['name']);
    $target_file = $upload_dir . $filename;

    // Cek apakah ada error dalam proses upload
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = '';
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'File upload stopped by extension';
                break;
            default:
                $error_message = 'Unknown upload error';
                break;
        }
        echo json_encode(['error' => 'Gagal mengunggah gambar', 'detail' => $error_message]);
        exit;
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Proses gambar
        $processed_dir = 'files_result/';
        $processed_filename = uniqid() . '.jpg';
        $processed_file = $processed_dir . $processed_filename;

        processImage($target_file, $processed_file, $target_width, $target_height);

        // Simpan metadata ke database
        $stmt = $pdo->prepare("INSERT INTO images (original_filename, processed_filename) VALUES (?, ?)");
        $stmt->execute([$filename, $processed_filename]);

        // Kembalikan URL dari gambar yang diproses
        echo json_encode(['url' => 'http://' . $_SERVER['HTTP_HOST'] . '/imager/' . $processed_file]);
    } else {
        echo json_encode(['error' => 'Gagal mengunggah gambar', 'detail' => 'move_uploaded_file function failed']);
    }
}

function processImage($source, $destination, $target_width, $target_height) {
    $image = imagecreatefromstring(file_get_contents($source));
    $width = imagesx($image);
    $height = imagesy($image);

    // Hitung scaling factor untuk menjaga aspek rasio
    $scaling_factor = min($target_width / $width, $target_height / $height);

    // Hitung ukuran baru
    $new_width = $width * $scaling_factor;
    $new_height = $height * $scaling_factor;

    // Buat canvas baru dengan ukuran target
    $canvas = imagecreatetruecolor($target_width, $target_height);

    // Blur background
    $background = imagecreatetruecolor($target_width, $target_height);
    imagecopyresampled($background, $image, 0, 0, 0, 0, $target_width, $target_height, $width, $height);
    for ($i = 0; $i < 50; $i++) {
        imagefilter($background, IMG_FILTER_GAUSSIAN_BLUR);
    }

    // Gabungkan gambar dengan background
    imagecopy($canvas, $background, 0, 0, 0, 0, $target_width, $target_height);
    imagecopyresampled($canvas, $image, ($target_width - $new_width) / 2, ($target_height - $new_height) / 2, 0, 0, $new_width, $new_height, $width, $height);

    // Simpan gambar yang sudah diproses
    imagejpeg($canvas, $destination, 90);

    // Bebaskan memori
    imagedestroy($image);
    imagedestroy($canvas);
    imagedestroy($background);
}
?>