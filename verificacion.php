<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["ok" => false, "errores" => ["Método no permitido"]]);
    exit;
}

if (empty($_FILES['pdf_file'])) {
    echo json_encode(["ok" => false, "errores" => ["No se ha enviado ningún PDF."]]);
    exit;
}

$archivos = $_FILES['pdf_file'];
$respuesta = [];

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$python = "C:\\Users\\JOEL-PC\\AppData\\Local\\Programs\\Python\\Python314\\python.exe";
$scriptPython = __DIR__ . DIRECTORY_SEPARATOR . "verificar_pdf.py";

foreach ($archivos['tmp_name'] as $i => $tmpName) {
    $nombreOriginal = $archivos['name'][$i];
    $tamano = $archivos['size'][$i];

    // Validar tamaño máximo 3MB
    if ($tamano > 3 * 1024 * 1024) {
        $respuesta[] = [
            "ok" => false,
            "archivo_original" => $nombreOriginal,
            "errores" => ["El archivo supera 3 MB."]
        ];
        continue;
    }

    $fileName = uniqid('pdf_', true) . ".pdf";
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetFile)) {
        $respuesta[] = [
            "ok" => false,
            "archivo_original" => $nombreOriginal,
            "errores" => ["No se pudo guardar el archivo."]
        ];
        continue;
    }

    // Ejecutar Python
    $cmd = escapeshellcmd("\"$python\" \"$scriptPython\" \"$targetFile\" 2>&1");
    $output = shell_exec($cmd);
    $output = trim(mb_convert_encoding($output, 'UTF-8', 'auto'));
    $output = preg_replace('/^\xEF\xBB\xBF/', '', $output);
    $resultado = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $respuesta[] = [
            "ok" => false,
            "archivo_original" => $nombreOriginal,
            "errores" => ["Error al interpretar la salida de Python."]
        ];
        continue;
    }

    $respuesta[] = [
        "ok" => true,
        "archivo_original" => $nombreOriginal,
        "archivo_servidor" => basename($targetFile),
        "tamano" => round($tamano / 1024, 2) . " KB",
        "resultado" => $resultado
    ];
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>



