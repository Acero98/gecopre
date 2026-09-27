"""Extrae únicamente el catálogo de red de la hoja CONTINUIDAD del Excel fuente."""

from __future__ import annotations

import json
import sys
from pathlib import Path

import openpyxl


def texto(valor: object) -> str:
    return "" if valor is None else str(valor).strip()


def main() -> None:
    archivo_entrada = Path(sys.argv[1])
    archivo_salida = Path(sys.argv[2])
    libro = openpyxl.load_workbook(archivo_entrada, data_only=False, read_only=False)
    hoja = libro["CONTINUIDAD"]

    actual = {"localidad": "", "zona_geografica": "", "fuente": "", "sector": "", "zona_presion": ""}
    puntos = []

    for fila in range(6, 114):
        valores = [texto(hoja.cell(fila, columna).value) for columna in range(1, 8)]
        for posicion, campo in enumerate(actual):
            if valores[posicion]:
                actual[campo] = valores[posicion]

        if not valores[6]:
            continue

        puntos.append({
            **actual,
            "codigo": valores[6],
            "direccion": valores[5],
        })

    if len(puntos) != 108:
        raise ValueError(f"Se esperaban 108 puntos de control y se encontraron {len(puntos)}.")

    archivo_salida.parent.mkdir(parents=True, exist_ok=True)
    archivo_salida.write_text(json.dumps({"origen": archivo_entrada.name, "puntos": puntos}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Catálogo extraído: {len(puntos)} puntos de control.")


if __name__ == "__main__":
    main()
