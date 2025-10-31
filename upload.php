<?php
header('Content-Type: application/json');

$uploadDir = __DIR__ . "/uploads/";
$outputDir = __DIR__ . "/salida/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$response = ["ok" => false, "mensaje" => "", "archivo_convertido" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["archivo_pdf"])) {
    $archivo = basename($_POST["archivo_pdf"]);
    $inputPath = $uploadDir . $archivo;
    $outputPath = $outputDir . "convertido_" . $archivo;

    if (!file_exists($inputPath)) {
        $response["mensaje"] = "❌ El archivo no existe en el servidor.";
        echo json_encode($response);
        exit;
    }

    $python = "C:\\Users\\CAAST-02\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    $cmd = escapeshellcmd("\"$python\" convertir.py " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath));
    
    // Ejecutar el script Python
    $output = shell_exec($cmd . " 2>&1");

    if (file_exists($outputPath)) {
        $response["ok"] = true;
        $response["mensaje"] = "✅ Conversión completada.";
        $response["archivo_convertido"] = "salida/" . basename($outputPath);
    } else {
        // Devuelve la salida de Python en el JSON, escapando saltos de línea
        $response["mensaje"] = "❌ Error en la conversión.\n" . strip_tags($output);
    }
} else {
    $response["mensaje"] = "❌ No se envió ningún archivo.";
}

echo json_encode($response);



