$(document).ready(function () {
    // 🔹 Variable global para guardar los archivos verificados
    let archivosVerificados = [];

    // Inicialización del DataTable
    var table = $('#producto_data').DataTable({
        data: [],
        columns: [
            { title: "Archivo" },
            { title: "Tamaño" },
            { title: "Mensaje" },
            { title: "Acciones" }
        ],
        language: { emptyTable: "No hay archivos seleccionados." },
        paging: false,
        searching: false,
        info: false
    });

    // Función para generar un ID único para los acordeones
    function generarIDUnico(base) {
        const sufijo = Math.random().toString(36).substr(2, 6);
        return `${base}_${sufijo}`;
    }

    // -------------------------------
    // 🔹 BOTÓN VERIFICAR
    // -------------------------------
    $('#btnVerificar').on('click', function(e) {
        e.preventDefault();

        var input = $('#pdf_file')[0];
        if (input.files.length === 0) {
            Swal.fire("Error", "Selecciona al menos un PDF.", "warning");
            return;
        }

        var formData = new FormData();
        for (let i = 0; i < input.files.length; i++) {
            formData.append('pdf_file[]', input.files[i]);
        }

        Swal.fire({
            title: 'Verificando PDFs...',
            html: 'Por favor espera...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'verificacion.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(respuesta) {
                Swal.close();

                // Limpiar tabla antes de agregar resultados nuevos
                table.clear();

                // 🔹 Reiniciar lista global
                archivosVerificados = [];

                respuesta.forEach(r => {
                    let mensajesHTML = "";

                    if (r.ok) {
                        let resultado = r.resultado;
                        let errores = resultado.errores || [];

                        let acordeonID = generarIDUnico(r.archivo_servidor);

                        if (errores.length > 0) {
                            mensajesHTML = `
                                <div class="alert alert-warning p-2 mb-0 small">
                                    ⚠️ ${errores.length} advertencia(s)
                                    <div class="accordion mt-2" id="acord_${acordeonID}">
                                        <div class="accordion-item border-0 bg-transparent">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-1 px-2 bg-light" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#det_${acordeonID}">
                                                    ▼ Ver detalles
                                                </button>
                                            </h2>
                                            <div id="det_${acordeonID}" class="accordion-collapse collapse">
                                                <div class="accordion-body p-2">
                                                    <ul class="mb-0">
                                                        ${errores.map(e => `<li>⚠️ ${e}</li>`).join("")}
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        } else {
                            mensajesHTML = `<div class="alert alert-success p-2 mb-0 small">✅ Sin errores detectados</div>`;
                        }

                        // 🔹 Guardar el nombre del archivo servidor para la conversión posterior
                        archivosVerificados.push(r.archivo_servidor);

                    } else {
                        mensajesHTML = `<div class="alert alert-danger p-2 small">${r.errores.join("<br>")}</div>`;
                    }

                    table.row.add([
                        r.archivo_original,
                        r.tamano || "-",
                        mensajesHTML,
                        `<a href="uploads/${r.archivo_servidor || ''}" target="_blank">Ver</a>`
                    ]).draw();
                });

                $('#pdf_file').val(""); // limpiar input
            },
            error: function(xhr, status, error) {
                Swal.close();
                Swal.fire("Error", "Error en la solicitud: " + error, "error");
            }
        });
    });

    // -------------------------------
    // 🔹 BOTÓN CONVERTIR
    // -------------------------------
    $(document).on('click', '#btn-convertir', function() {
        if (archivosVerificados.length === 0) {
            Swal.fire("Aviso", "No hay archivos cargados para convertir.", "info");
            return;
        }

        // Crear FormData con los nombres ya verificados
        let formData = new FormData();
        archivosVerificados.forEach(nombre => {
            formData.append('archivos_pdf[]', nombre);
        });

        Swal.fire({
            title: 'Convirtiendo PDF(s)...',
            html: 'Por favor espera mientras se procesan los archivos.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                Swal.close();

                if (response.ok) {
                    let lista = response.archivos_convertidos.map(a =>
                        `<li><a href="${a}" target="_blank">${a}</a></li>`
                    ).join("");
                    Swal.fire({
                        title: '✅ Conversión completada',
                        html: `<p>Archivos convertidos:</p><ul>${lista}</ul>`,
                        icon: 'success'
                    });
                } else {
                    Swal.fire({
                        title: '❌ Error en la conversión',
                        html: response.mensajes.join("<br>"),
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
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


