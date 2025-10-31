<?php
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] === UPLOAD_ERR_NO_FILE) {
        echo "<p style='color:red;'>❌ No se ha enviado ningún PDF.</p>";
        exit;
    }

    $file = $_FILES['pdf_file'];

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
        echo "<p style='color:red;'>❌ $mensaje</p>";
        exit;
    }

    $maxMB = 3;
    if ($file['size'] > $maxMB * 1024 * 1024) {
        echo "<p style='color:red;'>❌ El archivo supera $maxMB MB.</p>";
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = uniqid('pdf_') . ".pdf";
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo "<p style='color:red;'>❌ No se pudo guardar el archivo.</p>";
        exit;
    }

    $python = "C:\\Users\\CAAST-02\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    $scriptPython = __DIR__ . "\\verificar_pdf.py";

    if (!file_exists($python)) die("<p style='color:red;'>❌ Python no encontrado en: $python</p>");
    if (!file_exists($scriptPython)) die("<p style='color:red;'>❌ Script Python no encontrado en: $scriptPython</p>");

    $cmd = "\"$python\" \"$scriptPython\" \"$targetFile\" 2>&1";
    $output = shell_exec($cmd);
    $output = mb_convert_encoding($output, 'UTF-8', 'auto');  // Forzar UTF-8
    $resultado = json_decode($output, true);

    echo "<h2>Resultado de la verificación</h2>";

    if ($resultado && is_array($resultado)) {
        echo "<ul>";
        echo "<li>Archivo válido: " . ($resultado['archivo_valido'] ? "✅ Sí" : "❌ No") . "</li>";
        echo "<li>Cumple VUCEM: " . ($resultado['cumple_vucem'] ? "✅ Sí" : "❌ No") . "</li>";

        if (!empty($resultado['errores'])) {
            echo "<li>Errores:";
            echo "<ul>";
            foreach ($resultado['errores'] as $err) {
                echo "<li>❌ " . htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
            }
            echo "</ul></li>";
        }

        if (!empty($resultado['detalles'])) {
            echo "<li>Detalles del PDF:</li>";
            echo "<ul>";
            foreach ($resultado['detalles'] as $key => $val) {
                if (is_bool($val)) {
                    $icon = $val ? "❌" : "✅";
                    echo "<li>$key: $icon</li>";
                } elseif (is_array($val)) {
                    if (empty($val)) {
                        echo "<li>$key: ✅ Ninguno</li>";
                    } else {
                        $val_escapados = array_map(function($v){
                            return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        }, $val);
                        echo "<li>$key: ❌ " . implode(", ", $val_escapados) . "</li>";
                    }
                } else {
                    echo "<li>$key: " . htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
                }
            }
            echo "</ul>";
        }

        echo "</ul>";
    } else {
        echo "<p style='color:red;'>❌ Error al ejecutar el script Python o salida no válida:</p>";
        echo "<pre>" . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</pre>";
    }

} else {
    echo '<form method="post" enctype="multipart/form-data">
            <label for="pdf_file">Selecciona un PDF:</label>
            <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" required>
            <button type="submit">Verificar PDF</button>
          </form>';
}
?>


