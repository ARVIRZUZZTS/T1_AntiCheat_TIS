<?php

/**
 * @file    GenerarXlsxService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @description Genera un libro .xlsx minimo (sin dependencias externas) con
 * una hoja por cada arreglo de filas recibido. Usa cadenas en linea y no
 * formulas, suficiente para los reportes descargables.
 */

namespace App\Services\Reporte;

use RuntimeException;
use ZipArchive;

class GenerarXlsxService
{
    /**
     * @param  list<array{nombre: string, filas: list<list<string|int|float|null>>}>  $hojas
     * @return string Contenido binario del archivo .xlsx.
     */
    public function generar(array $hojas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'reporte_');

        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del reporte.');
        }

        $zip = new ZipArchive;

        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el libro de Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($hojas)));
        $zip->addFromString('_rels/.rels', $this->relsRaiz());
        $zip->addFromString('xl/workbook.xml', $this->workbook($hojas));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->relsWorkbook(count($hojas)));
        $zip->addFromString('xl/styles.xml', $this->estilos());

        foreach ($hojas as $indice => $hoja) {
            $zip->addFromString(
                'xl/worksheets/sheet'.($indice + 1).'.xml',
                $this->hoja($hoja['filas'])
            );
        }

        $zip->close();

        $contenido = file_get_contents($ruta);
        @unlink($ruta);

        if ($contenido === false) {
            throw new RuntimeException('No se pudo leer el libro de Excel generado.');
        }

        return $contenido;
    }

    private function contentTypes(int $cantidadHojas): string
    {
        $hojas = '';

        for ($i = 1; $i <= $cantidadHojas; $i++) {
            $hojas .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml"'
                .' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .$hojas
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function relsRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  list<array{nombre: string, filas: list<list<string|int|float|null>>}>  $hojas
     */
    private function workbook(array $hojas): string
    {
        $sheets = '';

        foreach ($hojas as $indice => $hoja) {
            $sheets .= '<sheet name="'.$this->escapar($this->nombreHoja($hoja['nombre'])).'"'
                .' sheetId="'.($indice + 1).'" r:id="rId'.($indice + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets>'
            .'</workbook>';
    }

    private function relsWorkbook(int $cantidadHojas): string
    {
        $relaciones = '';

        for ($i = 1; $i <= $cantidadHojas; $i++) {
            $relaciones .= '<Relationship Id="rId'.$i.'"'
                .' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                .' Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $relaciones .= '<Relationship Id="rId'.($cantidadHojas + 1).'"'
            .' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            .' Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relaciones
            .'</Relationships>';
    }

    private function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param  list<list<string|int|float|null>>  $filas
     */
    private function hoja(array $filas): string
    {
        $xmlFilas = '';

        foreach ($filas as $indiceFila => $fila) {
            $numeroFila = $indiceFila + 1;
            $estilo = $numeroFila === 1 ? ' s="1"' : '';
            $celdas = '';

            foreach ($fila as $indiceColumna => $valor) {
                if ($valor === null) {
                    continue;
                }

                $referencia = $this->referencia($indiceColumna, $numeroFila);

                if (is_int($valor) || is_float($valor)) {
                    $celdas .= '<c r="'.$referencia.'"'.$estilo.'><v>'.$valor.'</v></c>';
                    continue;
                }

                $celdas .= '<c r="'.$referencia.'" t="inlineStr"'.$estilo.'><is><t xml:space="preserve">'
                    .$this->escapar((string) $valor)
                    .'</t></is></c>';
            }

            $xmlFilas .= '<row r="'.$numeroFila.'">'.$celdas.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$xmlFilas.'</sheetData>'
            .'</worksheet>';
    }

    private function referencia(int $indiceColumna, int $fila): string
    {
        $letra = chr(65 + $indiceColumna);

        return $letra.$fila;
    }

    private function nombreHoja(string $nombre): string
    {
        return mb_substr(preg_replace('/[\[\]:*?\/\\\\]/', '', $nombre) ?? $nombre, 0, 31);
    }

    private function escapar(string $valor): string
    {
        return htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
