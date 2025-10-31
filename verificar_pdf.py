import sys
import os
import json
from PyPDF2 import PdfReader
from PIL import Image
import fitz  # PyMuPDF

def es_hoja_en_blanco(img, umbral=5):
    """Detecta si una página (imagen PIL en escala de grises) está en blanco."""
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
        "cumple_vucem": True,
        "errores": [],
        "detalles": {
            "tamano_MB": round(os.path.getsize(ruta_pdf) / (1024*1024), 2),
            "paginas": 0,
            "paginas_en_blanco": [],
            "paginas_no_grises": [],
            "paginas_con_ocr": [],
            "formularios_JS": False,
            "protegido": False
        }
    }

    if not os.path.exists(ruta_pdf):
        resultado['archivo_valido'] = False
        resultado['cumple_vucem'] = False
        resultado['errores'].append("El archivo no existe.")
        return resultado

    try:
        reader = PdfReader(ruta_pdf)
    except Exception:
        resultado['archivo_valido'] = False
        resultado['cumple_vucem'] = False
        resultado['errores'].append("No se puede abrir el PDF.")
        return resultado

    # Verificar protección
    if reader.is_encrypted:
        resultado['archivo_valido'] = False
        resultado['cumple_vucem'] = False
        resultado['errores'].append("PDF protegido con contraseña")
        resultado['detalles']['protegido'] = True

    # Verificar formularios y JavaScript
    try:
        root_obj = reader.trailer['/Root']
        if hasattr(root_obj, "get_object"):
            root_obj = root_obj.get_object()

        # Formularios
        if '/AcroForm' in root_obj:
            acro = root_obj['/AcroForm']
            if hasattr(acro, 'get_object'):
                acro = acro.get_object()
            if '/Fields' in acro and len(acro['/Fields']) > 0:
                resultado['archivo_valido'] = False
                resultado['cumple_vucem'] = False
                resultado['errores'].append("PDF contiene formularios")
                resultado['detalles']['formularios_JS'] = True

        # JavaScript incrustado
        if '/Names' in root_obj and '/JavaScript' in root_obj['/Names']:
            resultado['archivo_valido'] = False
            resultado['cumple_vucem'] = False
            resultado['errores'].append("PDF contiene JavaScript incrustado")
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

            # Escala de grises
            if not es_escala_grises(img):
                resultado['detalles']['paginas_no_grises'].append(i)

            # Hoja en blanco
            if es_hoja_en_blanco(img):
                resultado['detalles']['paginas_en_blanco'].append(i)

            # OCR detectado (si la página tiene texto sobre imagen)
            if pagina.get_text("text"):
                resultado['detalles']['paginas_con_ocr'].append(i)

        doc.close()

        # Marcar errores según resultados
        if resultado['detalles']['paginas_no_grises']:
            resultado['archivo_valido'] = False
            resultado['cumple_vucem'] = False
            resultado['errores'].append(
                f"Páginas no en escala de grises: {resultado['detalles']['paginas_no_grises']}"
            )

        if resultado['detalles']['paginas_en_blanco']:
            resultado['archivo_valido'] = False
            resultado['cumple_vucem'] = False
            resultado['errores'].append(
                f"Páginas en blanco: {resultado['detalles']['paginas_en_blanco']}"
            )

        if resultado['detalles']['paginas_con_ocr']:
            resultado['archivo_valido'] = False
            resultado['cumple_vucem'] = False
            resultado['errores'].append(
                f"Páginas con OCR sobre imagen: {resultado['detalles']['paginas_con_ocr']}"
            )

    except Exception as e:
        resultado['archivo_valido'] = False
        resultado['cumple_vucem'] = False
        resultado['errores'].append(f"Error al procesar páginas: {str(e)}")

    return resultado

if __name__ == "__main__":
    import io
    import sys

    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')  # Forzar UTF-8

    if len(sys.argv) < 2:
        print(json.dumps({"archivo_valido": False, "cumple_vucem": False, "errores": ["No se proporcionó PDF"]}, ensure_ascii=False))
    else:
        ruta = sys.argv[1]
        res = verificar_pdf(ruta)
        print(json.dumps(res, ensure_ascii=False))



