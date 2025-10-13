<?php
/**
 * PDFGenerator - Wrapper class for FPDF library to generate PDF reports
 * 
 * Requires FPDF library: http://www.fpdf.org/
 */
class PDFGenerator {
    private $pdf;
    private $title;
    private $author;
    private $created;
    private $pageWidth = 210; // A4 width in mm
    private $pageHeight = 297; // A4 height in mm
    private $margin = 10; // Margin in mm
    private $lineHeight = 6; // Line height in mm
    private $titleFontSize = 16;
    private $headerFontSize = 14;
    private $subHeaderFontSize = 12;
    private $textFontSize = 10;
    private $tableFontSize = 9;

  
    public function __construct() {
        // Require FPDF library
        require_once(BASE_PATH . '/vendor/fpdf/fpdf.php');
        
        // Initialize FPDF
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetAutoPageBreak(true, $this->margin);
        $this->pdf->SetMargins($this->margin, $this->margin, $this->margin);
        
        // Set default values
        $this->created = date('Y-m-d H:i:s');
        $this->title = 'Report';
        $this->author = 'Library Management System';
        
        // Add first page
        $this->pdf->AddPage();
        
        // Set font
        $this->pdf->SetFont('Arial', '', $this->textFontSize);
    }

    public function setOrientation($orientation) {
        // Recreate FPDF instance with new orientation
        $this->pdf = new FPDF($orientation, 'mm', 'A4');
        $this->pdf->SetAutoPageBreak(true, $this->margin);
        $this->pdf->SetMargins($this->margin, $this->margin, $this->margin);
        
        // Add first page
        $this->pdf->AddPage();
        
        // Reset font
        $this->pdf->SetFont('Arial', '', $this->textFontSize);
    }

    public function setTitle($title) {
        $this->title = $title;
        $this->pdf->SetTitle($title);
    }

    public function setAuthor($author) {
        $this->author = $author;
        $this->pdf->SetAuthor($author);
    }

    public function addHeader($text) {
        $this->pdf->SetFont('Arial', 'B', $this->titleFontSize);
        $this->pdf->Cell(0, $this->lineHeight, $text, 0, 1, 'C');
        $this->pdf->Ln($this->lineHeight / 2);
        $this->pdf->SetFont('Arial', '', $this->textFontSize);
    }

 
    public function addSubHeader($text) {
        $this->pdf->SetFont('Arial', 'B', $this->subHeaderFontSize);
        $this->pdf->Cell(0, $this->lineHeight, $text, 0, 1, 'L');
        $this->pdf->SetFont('Arial', '', $this->textFontSize);
    }


    public function addLine($text) {
        $this->pdf->Cell(0, $this->lineHeight, $text, 0, 1, 'L');
    }


    public function addSpace($height = null) {
        if ($height === null) {
            $height = $this->lineHeight;
        }
        $this->pdf->Ln($height);
    }

    public function addTable($columns, $data) {
        $this->pdf->SetFont('Arial', 'B', $this->tableFontSize);
        
        // Table header
        foreach ($columns as $column) {
            $this->pdf->Cell($column['width'], $this->lineHeight, $column['header'], 1, 0, 'C');
        }
        $this->pdf->Ln();
        
        // Table data
        $this->pdf->SetFont('Arial', '', $this->tableFontSize);
        
        // Check if there's data
        if (empty($data)) {
            $totalWidth = 0;
            foreach ($columns as $column) {
                $totalWidth += $column['width'];
            }
            $this->pdf->Cell($totalWidth, $this->lineHeight, 'No data available', 1, 1, 'C');
            return;
        }
        
        // Add data rows
        foreach ($data as $row) {
            // Check if we need a new page
            if ($this->pdf->GetY() > $this->pageHeight - $this->margin - $this->lineHeight) {
                $this->pdf->AddPage();
                
                // Repeat header on new page
                $this->pdf->SetFont('Arial', 'B', $this->tableFontSize);
                foreach ($columns as $column) {
                    $this->pdf->Cell($column['width'], $this->lineHeight, $column['header'], 1, 0, 'C');
                }
                $this->pdf->Ln();
                $this->pdf->SetFont('Arial', '', $this->tableFontSize);
            }
            
            foreach ($columns as $i => $column) {
                // Check if data exists for this column
                $cellData = isset($row[$i]) ? $row[$i] : '';
                
                // Truncate long text to prevent overflow
                if (strlen($cellData) > 50) {
                    $cellData = substr($cellData, 0, 47) . '...';
                }
                
                $this->pdf->Cell($column['width'], $this->lineHeight, $cellData, 1, 0, 'L');
            }
            $this->pdf->Ln();
        }
        $this->pdf->Ln($this->lineHeight / 2);
    }

    public function addImage($file, $x = null, $y = null, $width = 0, $height = 0) {
        if ($x === null) {
            $x = $this->pdf->GetX();
        }
        if ($y === null) {
            $y = $this->pdf->GetY();
        }
        
        $this->pdf->Image($file, $x, $y, $width, $height);
    }


    public function addPageBreak() {
        $this->pdf->AddPage();
    }

    public function output($disposition = 'I', $filename = null) {
        if ($filename === null) {
            $filename = $this->title . ' - ' . date('Y-m-d') . '.pdf';
        }
        
        return $this->pdf->Output($filename, $disposition);
    }


    public function generateBookReport($books, $title = 'Book Report') {
        $this->setTitle($title);
        
        // Add header
        $this->addHeader($title);
        $this->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $this->addSpace();
        
        // Define columns
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Title', 'width' => 60],
            ['header' => 'Author', 'width' => 50],
            ['header' => 'ISBN', 'width' => 30],
            ['header' => 'Category', 'width' => 25],
            ['header' => 'Copies', 'width' => 20]
        ];
        
        // Prepare data
        $data = [];
        foreach ($books as $book) {
            $data[] = [
                $book['id'],
                $book['title'],
                $book['author'],
                $book['isbn'],
                $book['category_name'] ?? 'N/A',
                $book['available_copies'] . '/' . $book['total_copies']
            ];
        }
        
        // Add table
        $this->addTable($columns, $data);
        
        return $this->output();
    }

  
    public function generateUserReport($users, $title = 'User Report') {
        $this->setTitle($title);
        
        // Add header
        $this->addHeader($title);
        $this->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $this->addSpace();
        
        // Define columns
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Username', 'width' => 30],
            ['header' => 'Name', 'width' => 50],
            ['header' => 'Email', 'width' => 60],
            ['header' => 'Role', 'width' => 25],
            ['header' => 'Status', 'width' => 20]
        ];
        
        // Prepare data
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                $user['id'],
                $user['username'],
                $user['first_name'] . ' ' . $user['last_name'],
                $user['email'],
                $user['role_name'] ?? 'Unknown',
                $user['is_active'] ? 'Active' : 'Inactive'
            ];
        }
        
        // Add table
        $this->addTable($columns, $data);
        
        return $this->output();
    }

 
    public function generateTransactionReport($transactions, $title = 'Transaction Report') {
        $this->setTitle($title);
        
        // Add header
        $this->addHeader($title);
        $this->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $this->addSpace();
        
        // Define columns
        $columns = [
            ['header' => 'ID', 'width' => 20],
            ['header' => 'Book', 'width' => 55],
            ['header' => 'Borrower', 'width' => 35],
            ['header' => 'Borrow Date', 'width' => 25],
            ['header' => 'Due Date', 'width' => 25],
            ['header' => 'Return Date', 'width' => 25],
            ['header' => 'Status', 'width' => 20]
        ];
        
        // Prepare data
        $data = [];
        foreach ($transactions as $transaction) {
            $returnDate = $transaction['return_date'] ? date('Y-m-d', strtotime($transaction['return_date'])) : 'Not Returned';
            
            $data[] = [
                $transaction['id'],
                $transaction['book_title'],
                $transaction['username'],
                date('Y-m-d', strtotime($transaction['borrow_date'])),
                date('Y-m-d', strtotime($transaction['due_date'])),
                $returnDate,
                $transaction['status_label']
            ];
        }
        
        // Add table
        $this->addTable($columns, $data);
        
        return $this->output();
    }
}
