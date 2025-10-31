<?php
header('Content-Type: text/html; charset=utf-8'); // <- Asegura UTF-8

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])) {

    // === CONFIGURAR DIRECTORIO DE SUBIDA ===
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = uniqid('pdf_') . ".pdf";
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetFile)) {
        die("❌ Error al subir el archivo.");
    }

    // === RUTAS DE PYTHON Y SCRIPT ===
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

        // 📄 Estado general del archivo
        echo "<li><strong>Archivo válido:</strong> " . ($resultado['archivo_valido'] ? "✅ Sí" : "❌ No") . "</li>";

        // ⚠️ Errores generales
        if (!empty($resultado['errores'])) {
            echo "<li><strong>Errores detectados:</strong><ul>";
            foreach ($resultado['errores'] as $err) {
                echo "<li>❌ " . htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
            }
            echo "</ul></li>";
        }

        // 📊 Detalles del análisis
        if (!empty($resultado['detalles'])) {
            $det = $resultado['detalles'];

            echo "<li><strong>Detalles:</strong><ul>";
            echo "<li>Tamaño del archivo: " . htmlspecialchars($det['tamano_MB']) . " MB</li>";
            echo "<li>Total de páginas: " . htmlspecialchars($det['paginas']) . "</li>";

            // ✅ Verificación de escala de grises
            if (isset($det['paginas_no_grises'])) {
                if (empty($det['paginas_no_grises'])) {
                    echo "<li><strong>Escala de grises:</strong> ✅ Cumple (todas las páginas son grises)</li>";
                } else {
                    $paginas_color = implode(", ", $det['paginas_no_grises']);
                    echo "<li><strong>Escala de grises:</strong> ❌ No cumple — Páginas con color: $paginas_color</li>";
                }
            }

            // 🧾 Páginas en blanco
            if (isset($det['paginas_en_blanco'])) {
                if (empty($det['paginas_en_blanco'])) {
                    echo "<li>Páginas en blanco: ✅ Ninguna</li>";
                } else {
                    echo "<li>Páginas en blanco: " . implode(", ", $det['paginas_en_blanco']) . "</li>";
                }
            }

            // 🔒 Formularios o JS
            echo "<li>Contiene formularios o JS: " . ($det['formularios_JS'] ? "❌ Sí" : "✅ No") . "</li>";

            // 🔐 PDF protegido
            echo "<li>PDF protegido: " . ($det['protegido'] ? "❌ Sí" : "✅ No") . "</li>";

            echo "</ul></li>";
        }

        echo "</ul>";

    } else {
        // ⚠️ Error si el script no devolvió JSON válido
        echo "<p style='color:red;'>Error al ejecutar el script Python o salida no válida:</p>";
        echo "<pre>" . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</pre>";
    }

} else {
    // 🧩 Formulario de subida
    echo '<form method="post" enctype="multipart/form-data">
            <h3>Verificar PDF</h3>
            <p>Seleccione un archivo PDF (máx. 3 MB):</p>
            <input type="file" name="pdf_file" accept="application/pdf" required>
            <br><br>
            <button type="submit">Verificar PDF</button>
          </form>';
}
?>













