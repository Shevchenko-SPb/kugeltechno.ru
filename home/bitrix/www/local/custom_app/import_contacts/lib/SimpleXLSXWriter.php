<?php

/**
 * SimpleXLSXWriter - простая библиотека для создания XLSX файлов
 */
class SimpleXLSXWriter {
    private $data = [];
    private $sheetName = 'Sheet1';
    
    public function __construct($sheetName = 'Sheet1') {
        $this->sheetName = $sheetName;
    }
    
    public function addRow($row) {
        $this->data[] = $row;
    }
    
    public function addRows($rows) {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }
    
    public function writeToFile($filename) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive не установлен');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            throw new Exception('Не удается создать ZIP архив');
        }
        
        // Добавляем основные файлы XLSX
        $this->addContentTypes($zip);
        $this->addRels($zip);
        $this->addApp($zip);
        $this->addCore($zip);
        $this->addWorkbookRels($zip);
        $this->addWorkbook($zip);
        $this->addWorksheet($zip);
        $this->addStyles($zip);
        
        $zip->close();
        
        return true;
    }
    
    private function addContentTypes($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $xml .= '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        $xml .= '</Types>';
        
        $zip->addFromString('[Content_Types].xml', $xml);
    }
    
    private function addRels($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>';
        $xml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>';
        $xml .= '</Relationships>';
        
        $zip->addFromString('_rels/.rels', $xml);
    }
    
    private function addApp($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">';
        $xml .= '<Application>SimpleXLSXWriter</Application>';
        $xml .= '<DocSecurity>0</DocSecurity>';
        $xml .= '<ScaleCrop>false</ScaleCrop>';
        $xml .= '<SharedDoc>false</SharedDoc>';
        $xml .= '<LinksUpToDate>false</LinksUpToDate>';
        $xml .= '</Properties>';
        
        $zip->addFromString('docProps/app.xml', $xml);
    }
    
    private function addCore($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        $xml .= '<dc:creator>SimpleXLSXWriter</dc:creator>';
        $xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>';
        $xml .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:modified>';
        $xml .= '</cp:coreProperties>';
        
        $zip->addFromString('docProps/core.xml', $xml);
    }
    
    private function addWorkbookRels($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';
        
        $zip->addFromString('xl/_rels/workbook.xml.rels', $xml);
    }
    
    private function addWorkbook($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        $xml .= '<sheet name="' . htmlspecialchars($this->sheetName) . '" sheetId="1" r:id="rId1"/>';
        $xml .= '</sheets>';
        $xml .= '</workbook>';
        
        $zip->addFromString('xl/workbook.xml', $xml);
    }
    
    private function addWorksheet($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetData>';
        
        $rowIndex = 1;
        foreach ($this->data as $row) {
            $xml .= '<row r="' . $rowIndex . '">';
            $colIndex = 1;
            foreach ($row as $cell) {
                $cellRef = $this->numberToColumn($colIndex) . $rowIndex;
                $cellValue = htmlspecialchars($cell ?? '', ENT_QUOTES, 'UTF-8');
                
                if (is_numeric($cell) && !is_string($cell)) {
                    $xml .= '<c r="' . $cellRef . '"><v>' . $cellValue . '</v></c>';
                } else {
                    $xml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . $cellValue . '</t></is></c>';
                }
                $colIndex++;
            }
            $xml .= '</row>';
            $rowIndex++;
        }
        
        $xml .= '</sheetData>';
        $xml .= '</worksheet>';
        
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    }
    
    private function addStyles($zip) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<numFmts count="0"/>';
        $xml .= '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>';
        $xml .= '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>';
        $xml .= '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>';
        $xml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $xml .= '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>';
        $xml .= '</styleSheet>';
        
        $zip->addFromString('xl/styles.xml', $xml);
    }
    
    private function numberToColumn($number) {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)) . $column;
            $number = intval($number / 26);
        }
        return $column;
    }
}
?>