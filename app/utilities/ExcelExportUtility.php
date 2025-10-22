<?php

class ExcelExportUtility
{
    private static function xmlHeader(): string
    {
        return "<?xml version=\"1.0\"?>\n" .
            "<?mso-application progid=\"Excel.Sheet\"?>\n" .
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
            . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
            . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    }

    private static function xmlFooter(): string
    {
        return "</Workbook>";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public static function outputSingleSheet(string $sheetName, array $headers, array $rows, string $filename): void
    {
        // Clear any existing output buffers to prevent error messages from leaking into Excel file
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Validate input data
        if (empty($headers) && empty($rows)) {
            throw new InvalidArgumentException('Cannot export empty data - no headers or rows provided');
        }
        
        // Send headers for Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');

        // Begin XML
        echo self::xmlHeader();
        echo '<Worksheet ss:Name="' . self::escape($sheetName) . '">';
        echo '<Table>'; 

        // Header row
        if (!empty($headers)) {
            echo '<Row>';
            foreach ($headers as $h) {
                echo '<Cell><Data ss:Type="String">' . self::escape($h) . '</Data></Cell>';
            }
            echo '</Row>';
        }

        // Data rows
        foreach ($rows as $row) {
            echo '<Row>';
            foreach ($row as $cell) {
                if (is_numeric($cell)) {
                    echo '<Cell><Data ss:Type="Number">' . $cell . '</Data></Cell>';
                } else {
                    echo '<Cell><Data ss:Type="String">' . self::escape((string)$cell) . '</Data></Cell>';
                }
            }
            echo '</Row>';
        }

        echo '</Table>';
        echo '</Worksheet>';
        echo self::xmlFooter();
        exit;
    }

    public static function outputKeyValueSheet(string $sheetName, array $pairs, string $filename): void
    {
        // Validate input data
        if (empty($pairs)) {
            throw new InvalidArgumentException('Cannot export empty key-value pairs');
        }
        
        $headers = ['Metric', 'Value'];
        $rows = [];
        foreach ($pairs as $k => $v) {
            $rows[] = [$k, $v];
        }
        self::outputSingleSheet($sheetName, $headers, $rows, $filename);
    }
}
