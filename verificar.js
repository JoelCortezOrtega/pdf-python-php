$(document).ready(function() {

    $('#btnVerificar').on('click', function(e) {
        e.preventDefault(); // Evita que el botón haga submit

        var fileInput = $('#pdf_file')[0];
        if (fileInput.files.length === 0) {
            Swal.fire("Error", "Selecciona un PDF primero", "warning");
            return;
        }

        var formData = new FormData();
        formData.append('pdf_file', fileInput.files[0]);

        $.ajax({
            url: 'verificacion.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.ok) {
                    var resultado = response.resultado;
                    var cumpleVucem = resultado.cumple_vucem ? "✅ Cumple" : "❌ No cumple";

                    var fila = `<tr>
                        <td>${response.archivo_original}</td>
                        <td>${response.tamano}</td>
                        <td>${cumpleVucem}</td>
                        <td><a href="uploads/${response.archivo_servidor}" target="_blank">Ver</a></td>
                    </tr>`;

                    $('#tbody').append(fila);
                    $('#pdf_file').val(""); // Limpiar input
                } else {
                    Swal.fire("Errores", response.errores.join("<br>"), "error");
                }
            },
            error: function(xhr, status, error) {
                Swal.fire("Error", "Error en la solicitud: " + error, "error");
            }
        });
    });

});

