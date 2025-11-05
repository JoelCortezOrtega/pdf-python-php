<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$uploadDir = __DIR__ . "/uploads/";
$outputDir = __DIR__ . "/salida/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$response = [
    "ok" => false,
    "mensajes" => [],
    "archivos_convertidos" => []
];

// Revisar si hay archivos enviados
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["archivos_pdf"])) {
    $archivos = $_POST["archivos_pdf"];

    // Asegurarse de que sea un array
    if (!is_array($archivos)) {
        $archivos = [$archivos];
    }

    $python = "C:\\Users\\JOEL-PC\\AppData\\Local\\Programs\\Python\\Python314\\python.exe";
    $scriptPython = __DIR__ . DIRECTORY_SEPARATOR . "convertir.py";

    foreach ($archivos as $archivo) {
        $archivo = basename($archivo);
        $inputPath = $uploadDir . $archivo;
        $outputPath = $outputDir . "convertido_" . $archivo;

        if (!file_exists($inputPath)) {
            $response["mensajes"][] = "❌ El archivo $archivo no existe en el servidor.";
            continue;
        }

        // Ejecutar el script de Python
        $cmd = "\"$python\" \"$scriptPython\" " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath);
        $output = shell_exec($cmd . " 2>&1");

        if (file_exists($outputPath)) {
            $response["archivos_convertidos"][] =  basename($outputPath);
            $response["mensajes"][] = "✅ $archivo convertido correctamente.";
        } else {
            $response["mensajes"][] = "❌ Error al convertir $archivo:\n" . strip_tags($output);
        }
    }

    // Si al menos uno se convirtió correctamente
    if (count($response["archivos_convertidos"]) > 0) {
        $response["ok"] = true;
    }

} else {
    $response["mensajes"][] = "❌ No se recibieron archivos válidos.";
}

// Enviar únicamente JSON limpio
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>



