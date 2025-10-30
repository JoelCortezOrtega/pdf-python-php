<?php
header('Content-Type: text/html; charset=utf-8'); // <- Asegura UTF-8

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])) {

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = uniqid('pdf_') . ".pdf";
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetFile)) {
        die("❌ Error al subir el archivo.");
    }

    // === CONFIGURAR RUTAS ===
    $python = "C:\\Users\\CAAST-02\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    $scriptPython = __DIR__ . "\\verificar_pdf.py";

    if (!file_exists($python)) die("❌ Python no encontrado en: $python");
    if (!file_exists($scriptPython)) die("❌ Script Python no encontrado en: $scriptPython");

    // === EJECUTAR PYTHON ===
    $cmd = "\"$python\" \"$scriptPython\" \"$targetFile\" 2>&1";
    $output = shell_exec($cmd);

    $resultado = json_decode($output, true);

    echo "<h2>Resultado de la verificación</h2>";

    if ($resultado && is_array($resultado)) {
        echo "<ul>";
        // Archivo válido
        echo "<li>Archivo válido: " . ($resultado['archivo_valido'] ? "✅ Sí" : "❌ No") . "</li>";

        // Errores generales
        if (!empty($resultado['errores'])) {
            echo "<li>Errores:";
            echo "<ul>";
            foreach ($resultado['errores'] as $err) {
                echo "<li>❌ " . htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
            }
            echo "</ul></li>";
        }

        // Detalles por campo
        if (!empty($resultado['detalles'])) {
            echo "<li>Detalles:</li>";
            echo "<ul>";
            foreach ($resultado['detalles'] as $key => $val) {
                if (is_bool($val)) {
                    // Para booleanos: true = ❌ (no cumple), false = ✅ (cumple)
                    $icon = $val ? "❌" : "✅";
                    echo "<li>$key: $icon</li>";
                } elseif (is_array($val)) {
                    // Para arrays: vacío = ✅ (ninguno), no vacío = ❌ listar elementos
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
        echo "<p style='color:red;'>Error al ejecutar el script Python o salida no válida:</p>";
        echo "<pre>" . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</pre>";
    }

} else {
    // Formulario de subida
    echo '<form method="post" enctype="multipart/form-data">
            Selecciona un PDF (máx 3 MB): 
            <input type="file" name="pdf_file" accept="application/pdf" required>
            <button type="submit">Verificar PDF</button>
          </form>';
}
?>






