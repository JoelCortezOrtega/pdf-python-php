import fitz  # PyMuPDF
from PIL import Image
import io
import os
import sys

def es_hoja_en_blanco(img, umbral=5):
    if img.mode != "L":
        img = img.convert("L")
    hist = img.histogram()
    total_pix = sum(hist)
    brillo_prom = sum(i * hist[i] for i in range(256)) / total_pix
    return brillo_prom > (255 - umbral)

def convertir_pdf_escala_grises(input_pdf, output_pdf, calidad_inicial=95):
    def generar_pdf(calidad):
        doc = fitz.open(input_pdf)
        nuevo_pdf = fitz.open()

        for num_pagina in range(len(doc)):
            pagina = doc.load_page(num_pagina)
            pix = pagina.get_pixmap(dpi=300)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
            img = img.convert("L")

            if es_hoja_en_blanco(img):
                continue

            img_byte_arr = io.BytesIO()
            img.save(img_byte_arr, format='JPEG', quality=calidad)
            img_byte_arr.seek(0)

            rect = fitz.Rect(0, 0, pix.width, pix.height)
            page = nuevo_pdf.new_page(width=rect.width, height=rect.height)
            page.insert_image(rect, stream=img_byte_arr.getvalue())

        nuevo_pdf.save(output_pdf, deflate=True, clean=True)
        nuevo_pdf.close()
        doc.close()

    calidad = calidad_inicial
    while calidad >= 40:
        generar_pdf(calidad)
        size_mb = os.path.getsize(output_pdf) / (1024 * 1024)
        if size_mb <= 3:
            break
        else:
            calidad -= 15

if __name__ == "__main__":
    if len(sys.argv) < 3:
        sys.exit(1)  # salir con error si no hay parámetros

    entrada = sys.argv[1]
    salida = sys.argv[2]  # ahora es archivo completo

    # Crear carpeta padre si no existe
    os.makedirs(os.path.dirname(salida), exist_ok=True)

    convertir_pdf_escala_grises(entrada, salida)
    print(os.path.basename(salida))







