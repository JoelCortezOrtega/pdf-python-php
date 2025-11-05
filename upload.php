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
    "archivos_convertidos" => [],
    "zip" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["archivos_pdf"])) {
    $archivos = $_POST["archivos_pdf"];
    if (!is_array($archivos)) $archivos = [$archivos];

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

        $cmd = "\"$python\" \"$scriptPython\" " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath);
        $output = shell_exec($cmd . " 2>&1");

        if (file_exists($outputPath)) {
            $response["archivos_convertidos"][] = basename($outputPath);
            $response["mensajes"][] = "✅ $archivo convertido correctamente.";
        } else {
            $response["mensajes"][] = "❌ Error al convertir $archivo:\n" . strip_tags($output);
        }
    }

    // Crear ZIP si hay archivos convertidos
    if (count($response["archivos_convertidos"]) > 0) {
        $zipName = "convertidos_" . date("Ymd_His") . ".zip";
        $zipPath = $outputDir . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($response["archivos_convertidos"] as $conv) {
                $zip->addFile($outputDir . $conv, $conv); // agrega solo el archivo, sin subcarpetas
            }
            $zip->close();
            $response["zip"] = "salida/" . $zipName;
        } else {
            $response["mensajes"][] = "❌ No se pudo crear el archivo ZIP.";
        }

        $response["ok"] = true;
    }
} else {
    $response["mensajes"][] = "❌ No se recibieron archivos válidos.";
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>






