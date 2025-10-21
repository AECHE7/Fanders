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

    public function addHeaderRaw($text) {
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

    /**
     * Generate a loan agreement PDF with details, schedule, terms, and signatures
     * @param array $loan Loan data with client information
     * @param array $paymentSchedule Weekly payment schedule
     * @param string $approvedBy Manager name who approved the loan
     * @return string PDF output
     */
    public function generateLoanAgreement($loan, $paymentSchedule, $approvedBy = 'Manager') {
        $this->setTitle('Loan Agreement - Loan #' . $loan['id']);
        $this->setAuthor('Fanders Microfinance');

        // Add header
        $this->addHeader('LOAN AGREEMENT');
        $this->addSubHeader('Fanders Microfinance');
        $this->addLine('Loan ID: ' . $loan['id']);
        $this->addLine('Date: ' . date('F d, Y'));
        $this->addSpace();

        // Borrower Information
        $this->addSubHeader('BORROWER INFORMATION');
        $this->addLine('Name: ' . htmlspecialchars($loan['client_name']));
        $this->addLine('Phone: ' . htmlspecialchars($loan['phone_number'] ?? 'N/A'));
        $this->addLine('Email: ' . htmlspecialchars($loan['email'] ?? 'N/A'));
        $this->addSpace();

        // Loan Details
        $this->addSubHeader('LOAN DETAILS');
        $this->addLine('Principal Amount: ₱' . number_format($loan['principal'], 2));
        $this->addLine('Interest Rate: ' . ($loan['interest_rate'] * 100) . '% per month');
        $this->addLine('Loan Term: ' . $loan['term_weeks'] . ' weeks');
        $this->addLine('Total Interest: ₱' . number_format($loan['total_interest'], 2));
        $this->addLine('Insurance Fee: ₱' . number_format($loan['insurance_fee'], 2));
        $this->addLine('Total Loan Amount: ₱' . number_format($loan['total_loan_amount'], 2));
        $this->addLine('Weekly Payment: ₱' . number_format($loan['total_loan_amount'] / $loan['term_weeks'], 2));
        $this->addSpace();

        // Payment Schedule
        $this->addSubHeader('PAYMENT SCHEDULE');
        $this->addLine('Payments are due every week starting from disbursement date.');
        $this->addSpace();

        // Define schedule columns
        $scheduleColumns = [
            ['header' => 'Week', 'width' => 20],
            ['header' => 'Due Date', 'width' => 30],
            ['header' => 'Payment Amount', 'width' => 35],
            ['header' => 'Principal', 'width' => 30],
            ['header' => 'Interest', 'width' => 25],
            ['header' => 'Insurance', 'width' => 25]
        ];

        // Prepare schedule data
        $scheduleData = [];
        $disbursementDate = strtotime($loan['disbursement_date'] ?? $loan['approval_date'] ?? date('Y-m-d'));

        foreach ($paymentSchedule as $payment) {
            $dueDate = date('M d, Y', strtotime("+" . ($payment['week'] - 1) . " weeks", $disbursementDate));
            $scheduleData[] = [
                $payment['week'],
                $dueDate,
                '₱' . number_format($payment['expected_payment'], 2),
                '₱' . number_format($payment['principal_payment'], 2),
                '₱' . number_format($payment['interest_payment'], 2),
                '₱' . number_format($payment['insurance_payment'], 2)
            ];
        }

        $this->addTable($scheduleColumns, $scheduleData);
        $this->addSpace();

        // Terms and Conditions
        $this->addSubHeader('TERMS AND CONDITIONS');
        $this->addLine('1. The borrower agrees to repay the loan in ' . $loan['term_weeks'] . ' equal weekly installments.');
        $this->addLine('2. Payments must be made on or before the due date each week.');
        $this->addLine('3. Late payments will incur a penalty of 2% of the weekly payment amount per week late.');
        $this->addLine('4. Failure to make payments may result in additional fees and collection actions.');
        $this->addLine('5. The borrower agrees to provide accurate information and notify of any changes.');
        $this->addLine('6. This agreement is governed by the laws of the Philippines.');
        $this->addSpace();

        // Signatures Section
        $this->addSubHeader('SIGNATURES');
        $this->addSpace(2);

        // Manager signature
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(80, 10, 'Approved By:', 0, 0);
        $this->pdf->Cell(80, 10, 'Borrower Signature:', 0, 1);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(80, 15, $approvedBy, 'T', 0);
        $this->pdf->Cell(80, 15, '', 'T', 1);
        $this->pdf->Cell(80, 10, 'Manager - Fanders Microfinance', 0, 0);
        $this->pdf->Cell(80, 10, htmlspecialchars($loan['client_name']), 0, 1);
        $this->pdf->Cell(80, 10, 'Date: ____________________', 0, 0);
        $this->pdf->Cell(80, 10, 'Date: ____________________', 0, 1);

        return $this->output();
    }

    /**
     * Generate a loan agreement PDF and save to file
     * @param array $loan Loan data with client information
     * @param array $paymentSchedule Weekly payment schedule
     * @param string $approvedBy Manager name who approved the loan
     * @param string $filePath Path to save the PDF file (optional)
     * @return bool Success status
     */
    public function generateLoanAgreementToFile($loan, $paymentSchedule, $approvedBy = 'Manager', $filePath = null) {
        $this->setTitle('Loan Agreement - Loan #' . $loan['id']);
        $this->setAuthor('Fanders Microfinance');

        // Set up professional styling
        $this->pdf->SetFillColor(240, 248, 255); // Light blue background for headers
        $this->pdf->SetDrawColor(0, 123, 255); // Blue border color
        $this->pdf->SetTextColor(33, 37, 41); // Dark text

        // Company Header with Logo Area
        $this->pdf->SetFont('Arial', 'B', 20);
        $this->pdf->SetFillColor(0, 123, 255);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 15, 'FANDERS MICROFINANCE', 0, 1, 'C', true);
        $this->pdf->SetTextColor(33, 37, 41);

        // Subtitle
        $this->pdf->SetFont('Arial', 'I', 12);
        $this->pdf->Cell(0, 8, 'Empowering Communities Through Financial Inclusion', 0, 1, 'C');
        $this->pdf->Ln(5);

        // Document Title
        $this->pdf->SetFont('Arial', 'B', 18);
        $this->pdf->SetFillColor(240, 248, 255);
        $this->pdf->Cell(0, 12, 'LOAN AGREEMENT', 1, 1, 'C', true);
        $this->pdf->Ln(3);

        // Agreement Details Box
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->Cell(95, 8, 'Loan ID: ' . $loan['id'], 1, 0, 'L', true);
        $this->pdf->Cell(95, 8, 'Date: ' . date('F d, Y'), 1, 1, 'L', true);
        $this->pdf->Ln(2);

        // Borrower Information Section
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetFillColor(0, 123, 255);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 10, 'BORROWER INFORMATION', 1, 1, 'L', true);
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->SetFont('Arial', '', 11);

        $this->pdf->Cell(40, 8, 'Full Name:', 1, 0, 'L');
        $this->pdf->Cell(0, 8, htmlspecialchars($loan['client_name']), 1, 1, 'L');

        $this->pdf->Cell(40, 8, 'Phone Number:', 1, 0, 'L');
        $this->pdf->Cell(0, 8, htmlspecialchars($loan['phone_number'] ?? 'N/A'), 1, 1, 'L');

        $this->pdf->Cell(40, 8, 'Email Address:', 1, 0, 'L');
        $this->pdf->Cell(0, 8, htmlspecialchars($loan['email'] ?? 'N/A'), 1, 1, 'L');

        $this->pdf->Cell(40, 8, 'Address:', 1, 0, 'L');
        $this->pdf->Cell(0, 8, htmlspecialchars($loan['address'] ?? 'N/A'), 1, 1, 'L');
        $this->pdf->Ln(3);

        // Loan Details Section
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetFillColor(0, 123, 255);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 10, 'LOAN DETAILS', 1, 1, 'L', true);
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->SetFont('Arial', '', 11);

        // Create a table for loan details
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->Cell(70, 8, 'Principal Amount:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, '₱' . number_format($loan['principal'], 2), 1, 1, 'R');

        $this->pdf->Cell(70, 8, 'Interest Rate:', 1, 0, 'L');
        $this->pdf->Cell(0, 8, ($loan['interest_rate'] * 100) . '% per month', 1, 1, 'R');

        $this->pdf->Cell(70, 8, 'Loan Term:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, $loan['term_weeks'] . ' weeks (' . round($loan['term_weeks']/4.33, 1) . ' months)', 1, 1, 'R');

        $this->pdf->Cell(70, 8, 'Total Interest:', 1, 0, 'L');
        $this->pdf->Cell(0, 8, '₱' . number_format($loan['total_interest'], 2), 1, 1, 'R');

        $this->pdf->Cell(70, 8, 'Insurance Fee:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, '₱' . number_format($loan['insurance_fee'], 2), 1, 1, 'R');

        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetFillColor(255, 193, 7);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(70, 10, 'TOTAL LOAN AMOUNT:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 10, '₱' . number_format($loan['total_loan_amount'], 2), 1, 1, 'R');

        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetFillColor(40, 167, 69);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(70, 10, 'WEEKLY PAYMENT:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 10, '₱' . number_format($loan['total_loan_amount'] / $loan['term_weeks'], 2), 1, 1, 'R');
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->Ln(3);

        // Payment Schedule Section
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetFillColor(0, 123, 255);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 10, 'PAYMENT SCHEDULE', 1, 1, 'L', true);
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->SetFont('Arial', '', 9);

        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->Cell(0, 6, 'Payments are due every week starting from disbursement date. All payments must be made on time.', 1, 1, 'L', true);
        $this->pdf->Ln(1);

        // Define schedule columns with better proportions
        $scheduleColumns = [
            ['header' => 'Week', 'width' => 15],
            ['header' => 'Due Date', 'width' => 25],
            ['header' => 'Payment Amount', 'width' => 35],
            ['header' => 'Principal', 'width' => 30],
            ['header' => 'Interest', 'width' => 25],
            ['header' => 'Insurance', 'width' => 25],
            ['header' => 'Balance', 'width' => 30]
        ];

        // Prepare schedule data with running balance
        $scheduleData = [];
        $runningBalance = $loan['total_loan_amount'];
        $disbursementDate = strtotime($loan['disbursement_date'] ?? $loan['approval_date'] ?? date('Y-m-d'));

        foreach ($paymentSchedule as $payment) {
            $runningBalance -= $payment['expected_payment'];
            $dueDate = date('M d, Y', strtotime("+" . ($payment['week'] - 1) . " weeks", $disbursementDate));
            $scheduleData[] = [
                $payment['week'],
                $dueDate,
                '₱' . number_format($payment['expected_payment'], 2),
                '₱' . number_format($payment['principal_payment'], 2),
                '₱' . number_format($payment['interest_payment'], 2),
                '₱' . number_format($payment['insurance_payment'], 2),
                '₱' . number_format(max(0, $runningBalance), 2)
            ];
        }

        $this->addTable($scheduleColumns, $scheduleData);
        $this->pdf->Ln(2);

        // Terms and Conditions Section
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetFillColor(220, 53, 69);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 10, 'TERMS AND CONDITIONS', 1, 1, 'L', true);
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->SetFont('Arial', '', 9);

        $terms = [
            '1. The borrower agrees to repay the loan in ' . $loan['term_weeks'] . ' equal weekly installments as specified in the payment schedule.',
            '2. Payments must be made on or before the due date each week. Late payments will incur penalties.',
            '3. Late payments will incur a penalty of 2% of the weekly payment amount per week late.',
            '4. Failure to make payments may result in additional fees, collection actions, and reporting to credit bureaus.',
            '5. The borrower agrees to provide accurate information and must notify Fanders Microfinance of any changes in contact information.',
            '6. The borrower authorizes Fanders Microfinance to verify information provided and to contact references if necessary.',
            '7. This agreement is governed by the laws of the Republic of the Philippines.',
            '8. Any disputes arising from this agreement shall be resolved through the proper courts of the Philippines.',
            '9. The borrower acknowledges receipt of a copy of this agreement and understands all terms and conditions.',
            '10. This agreement constitutes the entire understanding between the parties and supersedes all prior agreements.'
        ];

        $this->pdf->SetFillColor(255, 255, 255);
        foreach ($terms as $term) {
            $this->pdf->MultiCell(0, 5, $term, 0, 'L');
            $this->pdf->Ln(1);
        }
        $this->pdf->Ln(2);

        // Signatures Section
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetFillColor(0, 123, 255);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 10, 'SIGNATURES AND ACKNOWLEDGMENT', 1, 1, 'L', true);
        $this->pdf->SetTextColor(33, 37, 41);
        $this->pdf->Ln(5);

        // Create signature boxes
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->Cell(85, 8, 'APPROVED BY:', 1, 0, 'L');
        $this->pdf->Cell(85, 8, 'BORROWER SIGNATURE:', 1, 1, 'L');

        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(85, 15, $approvedBy, 'LTR', 0, 'L');
        $this->pdf->Cell(85, 15, '', 'LTR', 1, 'L');

        $this->pdf->Cell(85, 8, 'Manager - Fanders Microfinance', 'LBR', 0, 'L');
        $this->pdf->Cell(85, 8, htmlspecialchars($loan['client_name']), 'LBR', 1, 'L');

        $this->pdf->Cell(85, 8, 'Date: ____________________', 'LR', 0, 'L');
        $this->pdf->Cell(85, 8, 'Date: ____________________', 'LR', 1, 'L');

        $this->pdf->Cell(85, 8, '', 'LBR', 0, 'L');
        $this->pdf->Cell(85, 8, '', 'LBR', 1, 'L');
        $this->pdf->Ln(5);

        // Footer
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->SetTextColor(128, 128, 128);
        $this->pdf->Cell(0, 5, 'This document was generated electronically on ' . date('F d, Y H:i:s') . ' and is legally binding.', 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Fanders Microfinance - Contact: (02) 123-4567 | Email: info@fandersmicrofinance.com', 0, 1, 'C');

        // Save to file directly without sending to browser
        $this->pdf->Output($filePath, 'F');
        return true;
    }
}
