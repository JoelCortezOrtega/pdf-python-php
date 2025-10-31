<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); // evita mostrar errores HTML en la salida JSON

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["ok" => false, "errores" => ["Método no permitido"]]);
    exit;
}

if (empty($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(["ok" => false, "errores" => ["No se ha enviado ningún PDF."]]);
    exit;
}

$file = $_FILES['pdf_file'];

// 🔍 Manejamos errores de carga
if ($file['error'] !== UPLOAD_ERR_OK) {
    $mensajes = [
        UPLOAD_ERR_INI_SIZE => "El archivo supera el tamaño permitido.",
        UPLOAD_ERR_FORM_SIZE => "El archivo supera el tamaño permitido.",
        UPLOAD_ERR_PARTIAL => "El archivo se subió parcialmente.",
        UPLOAD_ERR_NO_FILE => "No se ha enviado ningún PDF.",
        UPLOAD_ERR_NO_TMP_DIR => "Falta carpeta temporal en el servidor.",
        UPLOAD_ERR_CANT_WRITE => "Error al escribir el archivo en el disco.",
        UPLOAD_ERR_EXTENSION => "Subida detenida por extensión PHP."
    ];
    $mensaje = $mensajes[$file['error']] ?? "Error desconocido al subir el archivo.";
    echo json_encode(["ok" => false, "errores" => [$mensaje]]);
    exit;
}

// 🧱 Validar tamaño máximo
$maxMB = 3;
if ($file['size'] > $maxMB * 1024 * 1024) {
    echo json_encode(["ok" => false, "errores" => ["El archivo supera {$maxMB} MB."]]);
    exit;
}

// 📁 Guardar en carpeta segura
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = uniqid('pdf_', true) . ".pdf";
$targetFile = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
    echo json_encode(["ok" => false, "errores" => ["No se pudo guardar el archivo."]]);
    exit;
}

// 🐍 Ejecutar script Python
$python = "C:\\Users\\CAAST-02\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
$scriptPython = __DIR__ . DIRECTORY_SEPARATOR . "verificar_pdf.py";

if (!file_exists($python) || !file_exists($scriptPython)) {
    echo json_encode(["ok" => false, "errores" => ["Python o script Python no encontrados."]]);
    exit;
}

// Comando seguro (escapa espacios y comillas)
$cmd = escapeshellcmd("\"$python\" \"$scriptPython\" \"$targetFile\" 2>&1");
$output = shell_exec($cmd);

// Forzar codificación UTF-8 y limpiar BOM si aparece
$output = trim(mb_convert_encoding($output, 'UTF-8', 'auto'));
$output = preg_replace('/^\xEF\xBB\xBF/', '', $output);

// Decodificar JSON de Python
$resultado = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "ok" => false,
        "errores" => [
            "Error al interpretar la salida de Python.",
            "Salida bruta:" => $output
        ]
    ]);
    exit;
}

// ✅ Respuesta JSON final al cliente
echo json_encode([
    "ok" => true,
    "archivo_original" => $file['name'],
    "archivo_servidor" => basename($targetFile),
    "tamano" => round($file['size'] / 1024, 2) . " KB",
    "resultado" => $resultado
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>


