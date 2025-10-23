# SLR (Statement of Loan Receipt) System Documentation

## Overview
The SLR (Statement of Loan Receipt) system provides comprehensive document management for loan disbursement records. This system replaces the previous loan agreement generation with a more robust, trackable, and secure document management solution.

## What is an SLR?
A **Statement of Loan Receipt (SLR)** is an official document that:
- Serves as proof that a client received loan funds
- Contains loan terms, repayment schedule, and borrower acknowledgment
- Provides legal documentation for loan disbursement
- Tracks document access and integrity

## When SLR Documents Are Generated

### 1. Manual Generation (Current Default)
- **Trigger**: Staff manually generates SLR from loan list
- **When**: After loan approval, when ready to disburse funds
- **Who**: Super-admin, Admin, Manager, Cashier
- **Process**: Click "SLR" button on loan list → Generate → Download

### 2. Automatic Generation (Configurable)
- **On Loan Approval**: Auto-generate when loan status changes to "Approved"
- **On Loan Disbursement**: Auto-generate when funds are disbursed
- **Configurable**: Can be enabled/disabled via generation rules

## SLR Workflow Process

### Step 1: Loan Approval
```
Loan Application → Review → Approval → SLR Eligible
```

### Step 2: SLR Generation
```
Manual Request → Generate SLR → Save to Database → Create PDF File
```

### Step 3: Document Management
```
SLR Created → Available for Download → Track Access → Archive when needed
```

### Step 4: Client Process
```
Download SLR → Print → Client Signs → File Original → Upload Signed Copy (future)
```

## Database Structure

### Tables Created:

#### 1. `slr_documents` (Main SLR records)
- **id**: Unique SLR document ID
- **loan_id**: Associated loan
- **document_number**: Unique document number (SLR-YYYYMM-LOANID)
- **generated_by**: User who generated the document
- **generation_trigger**: How it was generated (manual/auto_approval/auto_disbursement)
- **file_path**: Location of PDF file
- **file_name**: PDF filename
- **content_hash**: File integrity verification
- **download_count**: Number of times downloaded
- **status**: active/archived/replaced/invalid
- **client_signature_required**: Whether signature is needed
- **client_signed_at**: When client signed (future use)

#### 2. `slr_generation_rules` (Configuration)
- **rule_name**: Name of the rule
- **trigger_event**: When to generate (loan_approval/loan_disbursement/manual_request)
- **auto_generate**: Whether to auto-generate
- **require_signatures**: Whether client signature is required
- **is_active**: Whether rule is enabled

#### 3. `slr_access_log` (Audit Trail)
- **slr_document_id**: Which SLR was accessed
- **access_type**: view/download/print/email
- **accessed_by**: User who accessed
- **accessed_at**: When accessed
- **ip_address**: User's IP address
- **success**: Whether access was successful

## How to Use the SLR System

### For Loan Officers / Account Officers:

#### Generate SLR for a Loan:
1. Go to **Loans** → **List**
2. Find the approved loan
3. Click **"SLR"** button in Actions column
4. System generates and downloads PDF automatically

#### View SLR Status:
1. Go to **SLR** → **Manage**
2. Filter by loan ID or client
3. See generation history, download count, status

### For Admins / Managers:

#### Configure Auto-Generation:
1. Access SLR Generation Rules (admin panel)
2. Enable/disable automatic generation triggers
3. Set minimum/maximum loan amounts
4. Configure signature requirements

#### Archive Old SLRs:
1. Go to **SLR** → **Manage**
2. Select SLR document
3. Click **Archive** button
4. Provide reason for archival

### For Cashiers:

#### During Loan Disbursement:
1. Generate SLR if not already created
2. Print SLR document
3. Have client sign physical copy
4. Disburse funds
5. File signed original
6. Upload signed copy (future feature)

## SLR Document Content

### Header Information:
- Company name and address
- Document title: "STATEMENT OF LOAN RECEIPT (SLR)"
- SLR number and date issued

### Borrower Information:
- Client name, ID, address, contact number

### Loan Receipt Details:
- Loan ID, application date, receipt date
- Loan term (17 weeks), payment frequency (weekly)

### Amount Details:
- Principal amount received
- Total repayment amount
- Weekly payment amount

### Repayment Schedule:
- Number of payments (17 weekly)
- Weekly amount
- Expected completion date

### Signatures:
- Borrower signature and date
- Loan officer signature and date

## File Storage Structure

```
storage/
├── slr/                    # Active SLR documents
│   ├── SLR_202410_000001.pdf
│   ├── SLR_202410_000002.pdf
│   └── ...
├── slr/archive/           # Archived documents
│   └── archived_documents.pdf
└── slr/temp/              # Temporary files during generation
```

## Security Features

### Access Control:
- Role-based access (super-admin, admin, manager, cashier)
- Detailed access logging
- IP address tracking

### File Integrity:
- SHA-256 hash verification
- File corruption detection
- Secure file storage

### Audit Trail:
- Complete access history
- User activity tracking
- Download monitoring

## API Integration

### Generate SLR:
```php
$slrService = new SLRService();
$slrDocument = $slrService->generateSLR($loanId, $userId, 'manual');
```

### Download SLR:
```php
$fileInfo = $slrService->downloadSLR($slrId, $userId, 'reason');
```

### List SLRs:
```php
$slrDocuments = $slrService->listSLRDocuments($filters, $limit, $offset);
```

## Migration from Old System

### From Loan Agreement to SLR:
1. **Old System**: Generated agreements on loan approval
2. **New System**: Generates SLRs on demand with better tracking
3. **Benefit**: More control, better audit trail, document integrity

### Setup Process:
1. Run `php setup_slr_system.php` to create tables
2. Configure generation rules
3. Test with sample loans
4. Train staff on new workflow
5. Archive old documents

## Troubleshooting

### Common Issues:

#### "SLR already exists for this loan"
- **Cause**: Active SLR already generated
- **Solution**: Archive existing SLR first, then generate new one

#### "Failed to generate PDF"
- **Cause**: Missing PDF libraries or permissions
- **Solution**: Check PDFGenerator dependencies and file permissions

#### "File not found on disk"
- **Cause**: SLR file deleted or moved
- **Solution**: Check storage directory permissions and backup

#### "Loan not eligible for SLR"
- **Cause**: Loan status not approved/active/completed
- **Solution**: Ensure loan is approved before generating SLR

## Best Practices

### For Document Management:
1. **Generate SLR only when ready to disburse funds**
2. **Always download and print for client signature**
3. **Archive old SLRs if regenerating**
4. **Keep signed originals in physical files**
5. **Regular backup of SLR storage directory**

### For Security:
1. **Limit access to authorized personnel only**
2. **Monitor download activity regularly**
3. **Verify file integrity periodically**
4. **Use secure file storage location**

### For Compliance:
1. **Maintain complete audit trails**
2. **Follow document retention policies**
3. **Ensure client signatures are obtained**
4. **Keep records for regulatory requirements**

## Future Enhancements

### Planned Features:
1. **Digital Signatures**: Electronic client signatures
2. **Email Integration**: Send SLRs directly to clients
3. **Batch Generation**: Generate multiple SLRs at once
4. **Template Customization**: Configurable SLR templates
5. **Mobile Access**: Generate SLRs from mobile devices
6. **Integration with Disbursement**: Link with cash management
7. **Notification System**: Alert clients when SLR is ready

### Configuration Options:
1. **Auto-generation rules** based on loan criteria
2. **Custom document templates** for different loan types
3. **Signature requirements** by loan amount
4. **Retention policies** for document archival
5. **Access permissions** by user role

## Support and Training

### For Staff Training:
1. **Review this documentation**
2. **Practice with test loans**
3. **Understand security requirements**
4. **Learn troubleshooting steps**

### For Technical Support:
- Check error logs in system
- Verify database connectivity
- Test file permissions
- Review audit trails for issues

This SLR system provides a comprehensive solution for loan receipt documentation with enhanced security, tracking, and management capabilities compared to the previous loan agreement system.