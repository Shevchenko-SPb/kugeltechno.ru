<?php

/**
 * SimpleXLSX - минимальная рабочая версия для парсинга XLSX файлов
 */
class SimpleXLSX {
    private $sheets = [];
    private $sheetNames = [];
    private $sharedStrings = [];
    private static $error = '';
    
    public static function parse($filename) {
        if (!file_exists($filename)) {
            self::$error = 'Файл не найден';
            return false;
        }
        
        if (!class_exists('ZipArchive')) {
            self::$error = 'ZipArchive не установлен';
            return false;
        }
        
        $xlsx = new self();
        return $xlsx->parseFile($filename);
    }
    
    public static function parseError() {
        return self::$error;
    }
    
    private function parseFile($filename) {
        $zip = new ZipArchive();
        $result = $zip->open($filename);
        
        if ($result !== TRUE) {
            self::$error = 'Не удается открыть файл как ZIP архив';
            return false;
        }
        
        try {
            // Парсим workbook.xml для получения информации о листах
            $this->parseWorkbook($zip);
            
            // Парсим sharedStrings.xml
            $this->parseSharedStrings($zip);
            
            // Парсим листы
            $this->parseWorksheets($zip);
            
            $zip->close();
            return $this;
            
        } catch (Exception $e) {
            $zip->close();
            self::$error = $e->getMessage();
            return false;
        }
    }
    
    private function parseWorkbook($zip) {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml === false) {
            throw new Exception('Не найден workbook.xml');
        }
        
        $xml = simplexml_load_string($workbookXml);
        if ($xml === false) {
            throw new Exception('Ошибка парсинга workbook.xml');
        }
        
        // Регистрируем namespace
        $xml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->registerXPathNamespace('', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        
        $sheets = $xml->xpath('//sheet');
        foreach ($sheets as $sheet) {
            $sheetId = (int)$sheet['sheetId'];
            $sheetName = (string)$sheet['name'];
            $this->sheetNames[$sheetId - 1] = $sheetName;
        }
        
        if (empty($this->sheetNames)) {
            $this->sheetNames[0] = 'Sheet1';
        }
    }
    
    private function parseSharedStrings($zip) {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml === false) {
            return; // Файл может отсутствовать
        }
        
        $xml = simplexml_load_string($sharedStringsXml);
        if ($xml === false) {
            return;
        }
        
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $this->sharedStrings[] = (string)$si->t;
            } else if (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $text .= (string)$r->t;
                    }
                }
                $this->sharedStrings[] = $text;
            }
        }
    }
    
    private function parseWorksheets($zip) {
        foreach ($this->sheetNames as $sheetIndex => $sheetName) {
            $sheetFile = 'xl/worksheets/sheet' . ($sheetIndex + 1) . '.xml';
            $sheetXml = $zip->getFromName($sheetFile);
            
            if ($sheetXml === false) {
                continue;
            }
            
            $this->sheets[$sheetIndex] = $this->parseWorksheet($sheetXml);
        }
    }
    
    private function parseWorksheet($sheetXml) {
        $xml = simplexml_load_string($sheetXml);
        if ($xml === false) {
            return [];
        }
        
        $rows = [];
        
        foreach ($xml->sheetData->row as $row) {
            $rowIndex = (int)$row['r'] - 1;
            $rows[$rowIndex] = [];
            
            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r'];
                $cellType = (string)$cell['t'];
                $cellValue = (string)$cell->v;
                
                // Получаем координаты ячейки
                preg_match('/([A-Z]+)(\d+)/', $cellRef, $matches);
                if (count($matches) < 3) continue;
                
                $col = $this->columnIndexFromString($matches[1]);
                
                // Обрабатываем значение ячейки
                if ($cellType === 's' && isset($this->sharedStrings[$cellValue])) {
                    $value = $this->sharedStrings[$cellValue];
                } else if ($cellType === 'inlineStr') {
                    $value = (string)$cell->is->t;
                } else {
                    $value = $cellValue;
                }
                
                $rows[$rowIndex][$col] = $value;
            }
        }
        
        // Преобразуем в обычный массив
        $result = [];
        if (!empty($rows)) {
            $maxRow = max(array_keys($rows));
            $maxCol = 0;
            
            foreach ($rows as $row) {
                if (!empty($row)) {
                    $maxCol = max($maxCol, max(array_keys($row)));
                }
            }
            
            for ($r = 0; $r <= $maxRow; $r++) {
                $resultRow = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $resultRow[] = isset($rows[$r][$c]) ? $rows[$r][$c] : '';
                }
                $result[] = $resultRow;
            }
        }
        
        return $result;
    }
    
    private function columnIndexFromString($column) {
        $index = 0;
        $length = strlen($column);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }
        
        return $index - 1;
    }
    
    public function sheetNames() {
        return array_values($this->sheetNames);
    }
    
    public function rows($sheetIndex = 0) {
        return isset($this->sheets[$sheetIndex]) ? $this->sheets[$sheetIndex] : [];
    }
}
?>