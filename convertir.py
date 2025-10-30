import fitz  # PyMuPDF
from PIL import Image
import io
import os
import sys

def es_hoja_en_blanco(img, umbral=5):
    """Detecta si una página (imagen PIL en escala de grises) está en blanco."""
    if img.mode != "L":
        img = img.convert("L")

    hist = img.histogram()
    total_pix = sum(hist)
    brillo_prom = sum(i * hist[i] for i in range(256)) / total_pix
    return brillo_prom > (255 - umbral)

def convertir_pdf_escala_grises(input_pdf, output_pdf, calidad_inicial=95):
    """
    Convierte un PDF a:
    - Escala de grises 8 bits
    - 300 DPI
    - Sin hojas en blanco
    - Sin formularios ni JS
    - <3 MB con compresión adaptativa
    """
    def generar_pdf(calidad):
        doc = fitz.open(input_pdf)
        nuevo_pdf = fitz.open()

        for num_pagina in range(len(doc)):
            pagina = doc.load_page(num_pagina)
            pix = pagina.get_pixmap(dpi=300)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
            img = img.convert("L")

            # Saltar páginas en blanco
            if es_hoja_en_blanco(img):
                print(f"🧾 Página {num_pagina + 1} omitida (hoja en blanco).")
                continue

            # Convertir imagen a bytes comprimidos JPEG
            img_byte_arr = io.BytesIO()
            img.save(img_byte_arr, format='JPEG', quality=calidad)
            img_byte_arr.seek(0)

            # Crear una página del tamaño correcto en el nuevo PDF
            rect = fitz.Rect(0, 0, pix.width, pix.height)
            page = nuevo_pdf.new_page(width=rect.width, height=rect.height)

            # Insertar imagen dentro de la página
            page.insert_image(rect, stream=img_byte_arr.getvalue())

        nuevo_pdf.save(output_pdf, deflate=True, clean=True)
        nuevo_pdf.close()
        doc.close()

    # Compresión adaptativa
    calidad = calidad_inicial
    while calidad >= 40:
        generar_pdf(calidad)
        size_mb = os.path.getsize(output_pdf) / (1024 * 1024)
        print(f"📦 Intento con calidad {calidad}: {size_mb:.2f} MB")

        if size_mb <= 3:
            print(f"✅ Archivo final {output_pdf} ({size_mb:.2f} MB, calidad {calidad})")
            break
        else:
            calidad -= 15

    if calidad < 40:
        print("⚠️ No se logró bajar de 3 MB sin pérdida excesiva de calidad.")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Uso: python convertir.py entrada.pdf salida.pdf")
    else:
        entrada = sys.argv[1]
        salida = sys.argv[2]
        convertir_pdf_escala_grises(entrada, salida)




