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
            success: function(response) {
                if (response.ok) {
                    var resultado = response.resultado;
                    var detalles = resultado.detalles;

                    // Contar errores y advertencias
                    let totalErrores = resultado.errores ? resultado.errores.length : 0;
                    let advertenciasHTML = "";

                    if (totalErrores > 0) {
                        advertenciasHTML = `
                            <div class="alert alert-warning p-2 mb-0 small">
                                ⚠️ ${totalErrores} advertencia(s)
                                <div class="accordion mt-2" id="acord_${response.archivo_servidor}">
                                    <div class="accordion-item border-0 bg-transparent">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed py-1 px-2 bg-light" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#det_${response.archivo_servidor}">
                                                ▼ Ver detalles
                                            </button>
                                        </h2>
                                        <div id="det_${response.archivo_servidor}" class="accordion-collapse collapse">
                                            <div class="accordion-body p-2">
                                                <ul class="mb-0">
                                                    ${resultado.errores.map(e => `<li>⚠️ ${e}</li>`).join("")}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    } else {
                        advertenciasHTML = `<div class="alert alert-success p-2 mb-0 small">✅ Sin errores detectados</div>`;
                    }

                    // Construcción de la sección técnica
                    let revisionHTML = `
                        <div class="revision-card">
                            ${advertenciasHTML}
                        </div>`;

                    var fila = `
                        <tr>
                            <td>${response.archivo_original}</td>
                            <td>${response.tamano}</td>
                            <td>${revisionHTML}</td>
                            <td><a href="uploads/${response.archivo_servidor}" target="_blank">Ver</a></td>
                        </tr>
                    `;

                    $('#tbody').append(fila);
                    $('#pdf_file').val("");
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


// Evento delegado para los botones de detalles
$(document).on('click', '.ver-errores', function() {
    const erroresHTML = decodeURIComponent($(this).data('errores'));
    Swal.fire({
        title: 'Revisión técnica',
        html: `<ul style="text-align:left;">${erroresHTML}</ul>`,
        icon: 'info',
        confirmButtonText: 'Cerrar',
        width: 600
    });
});
