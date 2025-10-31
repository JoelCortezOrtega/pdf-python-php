# -*- coding: utf-8 -*-
import sys
import os
import io
import json
from PyPDF2 import PdfReader
from PIL import Image
import fitz  # PyMuPDF

# Forzar UTF-8 en salida estándar (necesario en Windows)
if sys.platform.startswith("win"):
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")


def es_hoja_en_blanco(img, umbral=5):
    """Detecta si una imagen es prácticamente blanca."""
    if img.mode != "L":
        img = img.convert("L")
    hist = img.histogram()
    total_pix = sum(hist)
    brillo_prom = sum(i * hist[i] for i in range(256)) / total_pix
    return brillo_prom > (255 - umbral)


def es_escala_grises(img):
    """Detecta si una imagen es realmente en escala de grises."""
    if img.mode != "RGB":
        img = img.convert("RGB")
    # Muestrea cada 20 píxeles para mayor velocidad
    pix = img.load()
    for x in range(0, img.width, 20):
        for y in range(0, img.height, 20):
            r, g, b = pix[x, y]
            if r != g or g != b:
                return False
    return True


def verificar_pdf(ruta_pdf):
    resultado = {
        "archivo_valido": True,
        "errores": [],
        "detalles": {
            "tamano_MB": round(os.path.getsize(ruta_pdf) / (1024 * 1024), 2),
            "paginas": 0,
            "paginas_en_blanco": [],
            "paginas_no_grises": [],
            "formularios_JS": False,
            "protegido": False
        }
    }

    if not os.path.exists(ruta_pdf):
        resultado['archivo_valido'] = False
        resultado['errores'].append("El archivo no existe.")
        return resultado

    # Intentar abrir PDF
    try:
        reader = PdfReader(ruta_pdf)
    except Exception:
        resultado['archivo_valido'] = False
        resultado['errores'].append("No se puede abrir el PDF.")
        return resultado

    # Verificar protección
    if reader.is_encrypted:
        resultado['archivo_valido'] = False
        resultado['errores'].append("PDF protegido con contraseña")
        resultado['detalles']['protegido'] = True

    # Verificar formularios y JS
    try:
        root_obj = reader.trailer['/Root']
        if hasattr(root_obj, "get_object"):
            root_obj = root_obj.get_object()

        if '/AcroForm' in root_obj:
            resultado['archivo_valido'] = False
            resultado['errores'].append("PDF contiene formularios incrustados (AcroForm)")
            resultado['detalles']['formularios_JS'] = True
        elif '/Names' in root_obj:
            names = root_obj['/Names']
            if hasattr(names, "get_object"):
                names = names.get_object()
            if '/JavaScript' in names:
                resultado['archivo_valido'] = False
                resultado['errores'].append("PDF contiene scripts JavaScript incrustados")
                resultado['detalles']['formularios_JS'] = True

    except Exception as e:
        resultado['errores'].append(f"No se pudo verificar formularios/JS: {e}")

    # Verificar páginas
    try:
        doc = fitz.open(ruta_pdf)
        resultado['detalles']['paginas'] = len(doc)

        for i, pagina in enumerate(doc, start=1):
            pix = pagina.get_pixmap(dpi=150)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)

            # Verificar escala de grises
            if not es_escala_grises(img):
                resultado['detalles']['paginas_no_grises'].append(i)

            # Verificar si la página está en blanco
            if es_hoja_en_blanco(img):
                resultado['detalles']['paginas_en_blanco'].append(i)

        doc.close()

        # Si hay páginas con color → marcar como no válido
        if resultado['detalles']['paginas_no_grises']:
            resultado['archivo_valido'] = False
            resultado['errores'].append(
                f"Páginas no en escala de grises: {resultado['detalles']['paginas_no_grises']}"
            )

    except Exception as e:
        resultado['archivo_valido'] = False
        resultado['errores'].append(f"Error al procesar páginas: {str(e)}")

    return resultado


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"archivo_valido": False, "errores": ["No se proporcionó PDF"]}, ensure_ascii=False))
    else:
        ruta = sys.argv[1]
        res = verificar_pdf(ruta)
        print(json.dumps(res, ensure_ascii=False))



