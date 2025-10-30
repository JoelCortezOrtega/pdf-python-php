import sys
import os
import json
from PyPDF2 import PdfReader
from PIL import Image
import fitz  # PyMuPDF

def es_hoja_en_blanco(img, umbral=5):
    if img.mode != "L":
        img = img.convert("L")
    hist = img.histogram()
    total_pix = sum(hist)
    brillo_prom = sum(i * hist[i] for i in range(256)) / total_pix
    return brillo_prom > (255 - umbral)

def verificar_pdf(ruta_pdf):
    resultado = {
        "archivo_valido": True,
        "errores": [],
        "detalles": {
            "tamano_MB": round(os.path.getsize(ruta_pdf) / (1024*1024), 2),
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

    try:
        reader = PdfReader(ruta_pdf)
    except:
        resultado['archivo_valido'] = False
        resultado['errores'].append("No se puede abrir el PDF.")
        return resultado

    # Verificar protección y formularios/JS
    if reader.is_encrypted:
        resultado['archivo_valido'] = False
        resultado['errores'].append("PDF protegido con contraseña")
        resultado['detalles']['protegido'] = True
    if '/AcroForm' in reader.trailer['/Root'] or '/JS' in reader.trailer['/Root']:
        resultado['archivo_valido'] = False
        resultado['errores'].append("PDF contiene formularios u objetos incrustados")
        resultado['detalles']['formularios_JS'] = True

    # Verificar páginas
    num_paginas = len(reader.pages)
    resultado['detalles']['paginas'] = num_paginas

    try:
        doc = fitz.open(ruta_pdf)
        for i in range(len(doc)):
            pagina = doc.load_page(i)
            pix = pagina.get_pixmap(dpi=300)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
            img = img.convert("L")

            if es_hoja_en_blanco(img):
                resultado['detalles']['paginas_en_blanco'].append(i+1)

            if img.mode != "L":
                resultado['archivo_valido'] = False
                resultado['errores'].append(f"Página {i+1} no está en escala de grises")
                resultado['detalles']['paginas_no_grises'].append(i+1)

    except Exception as e:
        resultado['archivo_valido'] = False
        resultado['errores'].append(f"Error al procesar páginas: {str(e)}")

    return resultado

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"archivo_valido": False, "errores": ["No se proporcionó PDF"]}))
    else:
        ruta = sys.argv[1]
        res = verificar_pdf(ruta)
        print(json.dumps(res, ensure_ascii=False))

