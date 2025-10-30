<?php
// Configuración de rutas
$uploadDir = __DIR__ . "/uploads/";
$outputDir = __DIR__ . "/salida/";

// Crear carpetas si no existen
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["archivo_pdf"])) {
    $fileTmp = $_FILES["archivo_pdf"]["tmp_name"];
    $fileName = basename($_FILES["archivo_pdf"]["name"]);

    // Validar tipo de archivo
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileType !== "pdf") {
        die("❌ Solo se permiten archivos PDF.");
    }

    $inputPath = $uploadDir . $fileName;
    $outputPath = $outputDir . "convertido_" . $fileName;

    // Mover archivo subido a carpeta "uploads"
    if (move_uploaded_file($fileTmp, $inputPath)) {
        echo "✅ PDF subido correctamente.<br>";

        // Ejecutar el script Python usando ruta completa
        $python = "C:\\Users\\CAAST-02\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
        $cmd = escapeshellcmd("\"$python\" convertir.py " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath));
        $output = shell_exec($cmd . " 2>&1");

        echo "<pre>$output</pre>";

        if (file_exists($outputPath)) {
            echo "✅ Conversión completada. <a href='salida/" . basename($outputPath) . "'>Descargar PDF convertido</a>";
        } else {
            echo "❌ Error: no se generó el PDF convertido.";
        }
    } else {
        echo "❌ Error al subir el archivo.";
    }
}
?>

