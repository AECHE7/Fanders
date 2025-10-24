<?php

class ExcelExportUtility
{
    private static function sanitizeFilename(string $filename, string $fallback = 'export.xls'): string
    {
        // Prevent header injection and strip path characters
        $sanitized = str_replace(["\r", "\n"], '', $filename);
        $sanitized = basename($sanitized);
        // Remove any remaining dangerous characters
        $sanitized = preg_replace('/[^A-Za-z0-9._\- ]+/', '', $sanitized) ?: $fallback;
        return $sanitized;
    }
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
        // Use safe export wrapper to prevent warnings from contaminating output
        SafeExportWrapper::beginSafeExport();
        
        // Validate input data
        if (empty($headers) && empty($rows)) {
            SafeExportWrapper::endSafeExport();
            throw new InvalidArgumentException('Cannot export empty data - no headers or rows provided');
        }
        
        try {
            // Send headers for Excel
            $fname = self::sanitizeFilename($filename);
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Transfer-Encoding: binary');
            // RFC 6266 / RFC 5987 for UTF-8 filenames support
            header('Content-Disposition: attachment; filename="' . $fname . '"; filename*=UTF-8\'' . rawurlencode($fname) . "'");
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
        
        // Restore safe export environment
        SafeExportWrapper::endSafeExport();
        exit;
        
    } catch (Exception $e) {
        // Restore safe export environment before re-throwing
        SafeExportWrapper::endSafeExport();
        throw $e;
    } catch (Error $e) {
        // Restore safe export environment before re-throwing
        SafeExportWrapper::endSafeExport();
        throw $e;
    }
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
