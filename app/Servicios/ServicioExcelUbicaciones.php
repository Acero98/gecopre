<?php
declare(strict_types=1);

namespace App\Servicios;

/**
 * Genera y lee la plantilla XLSX de ubicaciones sin depender de paquetes externos.
 * El formato se mantiene deliberadamente simple: una sola hoja y celdas de texto/número.
 */
final class ServicioExcelUbicaciones
{
    public const ENCABEZADOS = ['codigo', 'direccion', 'zona_geografica', 'sector_operacional', 'zona_presion', 'latitud', 'longitud'];

    public function descargar(array $filas): never
    {
        $temporal = tempnam(sys_get_temp_dir(), 'gecopre_ubicaciones_');
        if ($temporal === false) {
            throw new \RuntimeException('No se pudo preparar la plantilla.');
        }

        $archivo = $temporal . '.xlsx';
        @unlink($temporal);
        $this->crearLibro($archivo, $filas);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plantilla_ubicaciones_puntos.xlsx"');
        header('Content-Length: ' . (string) filesize($archivo));
        header('Cache-Control: no-store');
        readfile($archivo);
        @unlink($archivo);
        exit;
    }

    /** @return list<array<string, string|float|null>> */
    public function leer(string $archivo): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivo) !== true) {
            throw new \DomainException('El archivo no es un libro Excel válido.');
        }
        try {
            $xmlHoja = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!is_string($xmlHoja)) {
                throw new \DomainException('No se encontró la primera hoja del Excel.');
            }
            $compartidas = $this->leerCadenasCompartidas($zip->getFromName('xl/sharedStrings.xml'));
            $xml = simplexml_load_string($xmlHoja);
            if (!$xml) {
                throw new \DomainException('No se pudo leer la hoja del Excel.');
            }
            $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $filasXml = $xml->xpath('//x:sheetData/x:row') ?: [];
            if ($filasXml === []) {
                throw new \DomainException('El Excel no contiene registros.');
            }

            $filas = [];
            foreach ($filasXml as $numero => $filaXml) {
                $valores = $this->leerFila($filaXml, $compartidas);
                if ($numero === 0) {
                    $columnas = [];
                    foreach ($valores as $indice => $encabezado) {
                        $nombre = $this->nombreColumna((string) $encabezado);
                        if ($nombre !== null && !isset($columnas[$nombre])) $columnas[$nombre] = $indice;
                    }
                    foreach (['codigo', 'latitud', 'longitud'] as $requerido) {
                        if (!isset($columnas[$requerido])) {
                            throw new \DomainException('El Excel debe incluir las columnas código, latitud y longitud. Puedes usar la plantilla o el formato recibido de puntos de presión.');
                        }
                    }
                    continue;
                }
                if (trim(implode('', $valores)) === '') {
                    continue;
                }
                $fila = [];
                foreach (self::ENCABEZADOS as $encabezado) {
                    $indice = $columnas[$encabezado] ?? null;
                    $fila[$encabezado] = $indice !== null && isset($valores[$indice]) ? trim((string) $valores[$indice]) : '';
                }
                $fila['_fila'] = $numero + 1;
                $filas[] = $fila;
            }
            return $filas;
        } finally {
            $zip->close();
        }
    }

    /** @return list<string> */
    private function leerCadenasCompartidas(string|false $contenido): array
    {
        if (!is_string($contenido)) return [];
        $xml = simplexml_load_string($contenido);
        if (!$xml) return [];
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cadenas = [];
        foreach ($xml->xpath('//x:si') ?: [] as $nodo) {
            $nodo->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $partes = $nodo->xpath('.//x:t') ?: [];
            $cadenas[] = implode('', array_map(static fn ($parte): string => (string) $parte, $partes));
        }
        return $cadenas;
    }

    /** @return list<string> */
    private function leerFila(\SimpleXMLElement $fila, array $compartidas): array
    {
        $fila->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $celdas = $fila->xpath('./x:c') ?: [];
        $valores = [];
        foreach ($celdas as $celda) {
            $referencia = (string) ($celda['r'] ?? 'A1');
            preg_match('/([A-Z]+)/', $referencia, $coincidencia);
            $indice = $this->indiceColumna($coincidencia[1] ?? 'A');
            $tipo = (string) ($celda['t'] ?? '');
            $valor = $tipo === 'inlineStr' ? (string) $celda->is->t : (string) $celda->v;
            if ($tipo === 's') $valor = $compartidas[(int) $valor] ?? '';
            $valores[$indice] = $valor;
        }
        if ($valores === []) return [];
        ksort($valores);
        return array_map(static fn (int $indice) => $valores[$indice] ?? '', range(0, max(array_keys($valores))));
    }

    private function indiceColumna(string $letras): int
    {
        $valor = 0;
        foreach (str_split($letras) as $letra) $valor = $valor * 26 + (ord($letra) - 64);
        return $valor - 1;
    }

    private function normalizarEncabezado(string $valor): string
    {
        $valor = trim(mb_strtolower($valor, 'UTF-8'));
        $valor = strtr($valor, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $valor), '_');
    }

    private function nombreColumna(string $encabezado): ?string
    {
        $normalizado = $this->normalizarEncabezado($encabezado);
        return match ($normalizado) {
            'codigo', 'codigo_punto', 'codigo_punto_control' => 'codigo',
            'direccion', 'direccion_punto', 'direccion_punto_control' => 'direccion',
            'zona_geografica', 'zona_geografica_nombre' => 'zona_geografica',
            'sector_operacional', 'sector' => 'sector_operacional',
            'zona_presion', 'zona_de_presion' => 'zona_presion',
            'latitud', 'latitud_decimal', 'latitud_grados_decimal' => 'latitud',
            'longitud', 'longitud_decimal', 'longitud_grados_decimal' => 'longitud',
            default => null,
        };
    }

    private function crearLibro(string $archivo, array $filas): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivo, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo generar la plantilla Excel.');
        }
        $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="38" customWidth="1"/><col min="3" max="3" width="28" customWidth="1"/><col min="4" max="4" width="22" customWidth="1"/><col min="5" max="5" width="18" customWidth="1"/><col min="6" max="7" width="16" customWidth="1"/></cols><sheetData>';
        $hoja .= $this->filaXml(1, self::ENCABEZADOS, true);
        foreach (array_values($filas) as $indice => $fila) {
            $valores = array_map(static fn (string $encabezado) => $fila[$encabezado] ?? '', self::ENCABEZADOS);
            $hoja .= $this->filaXml($indice + 2, $valores, false);
        }
        $hoja .= '</sheetData><autoFilter ref="A1:G' . (count($filas) + 1) . '"/></worksheet>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Ubicaciones" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1768BB"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellXfs count="2"><xf fontId="0" fillId="0" borderId="0"/><xf fontId="1" fillId="1" borderId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
        $zip->close();
    }

    private function filaXml(int $numero, array $valores, bool $encabezado): string
    {
        $xml = '<row r="' . $numero . '">';
        foreach (array_values($valores) as $indice => $valor) {
            $columna = chr(65 + $indice);
            $referencia = $columna . $numero;
            if (!$encabezado && in_array($indice, [5, 6], true) && is_numeric($valor)) {
                $xml .= '<c r="' . $referencia . '"><v>' . htmlspecialchars((string) $valor, ENT_XML1) . '</v></c>';
            } else {
                $xml .= '<c r="' . $referencia . '" t="inlineStr"' . ($encabezado ? ' s="1"' : '') . '><is><t>' . htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
            }
        }
        return $xml . '</row>';
    }
}
